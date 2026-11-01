import fs from 'fs';
import path from 'path';

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const sourceDir = path.join(root, 'public/catalog/meemare25');
const outDir = path.join(root, 'public/catalog/meemare25-norm');
const catalogPath = path.join(root, 'public/data/wardrobe-glb-modules.json');

const ids = fs.readdirSync(sourceDir).filter((name) => fs.existsSync(path.join(sourceDir, name, 'model.glb'))).sort(sortId);

function sortId(a, b) {
    const rank = (id) => (id.startsWith('tek') ? 0 : id.startsWith('cift') ? 1 : 2);
    const num = (id) => Number(id.match(/(\d+)$/)?.[1] || 0);
    return rank(a) - rank(b) || num(a) - num(b) || a.localeCompare(b);
}

function readGlb(file) {
    const buf = fs.readFileSync(file);
    if (buf.toString('utf8', 0, 4) !== 'glTF') throw new Error('glb değil');
    const jsonLength = buf.readUInt32LE(12);
    const json = JSON.parse(buf.slice(20, 20 + jsonLength).toString('utf8'));
    const binStart = 20 + jsonLength;
    const binLength = buf.readUInt32LE(binStart);
    const bin = buf.slice(binStart + 8, binStart + 8 + binLength);
    return { json, bin };
}

function writeGlb(file, json, bin) {
    const jsonText = JSON.stringify(json);
    const jsonPad = (4 - (jsonText.length % 4)) % 4;
    const jsonChunk = Buffer.concat([Buffer.from(jsonText), Buffer.alloc(jsonPad, 0x20)]);
    const binPad = (4 - (bin.length % 4)) % 4;
    const binChunk = Buffer.concat([bin, Buffer.alloc(binPad, 0)]);
    const total = 12 + 8 + jsonChunk.length + 8 + binChunk.length;
    const header = Buffer.alloc(12);
    header.write('glTF', 0);
    header.writeUInt32LE(2, 4);
    header.writeUInt32LE(total, 8);
    const c0 = Buffer.alloc(8);
    c0.writeUInt32LE(jsonChunk.length, 0);
    c0.writeUInt32LE(0x4e4f534a, 4);
    const c1 = Buffer.alloc(8);
    c1.writeUInt32LE(binChunk.length, 0);
    c1.writeUInt32LE(0x004e4942, 4);
    fs.writeFileSync(file, Buffer.concat([header, c0, jsonChunk, c1, binChunk]));
}

function meshBoxes(json) {
    return json.meshes.map((mesh) => {
        const acc = json.accessors[mesh.primitives[0].attributes.POSITION];
        return { name: mesh.name || '', min: acc.min.slice(), max: acc.max.slice() };
    });
}

function union(boxes) {
    const min = [Infinity, Infinity, Infinity];
    const max = [-Infinity, -Infinity, -Infinity];
    boxes.forEach((box) => {
        for (let i = 0; i < 3; i += 1) {
            min[i] = Math.min(min[i], box.min[i]);
            max[i] = Math.max(max[i], box.max[i]);
        }
    });
    return { min, max, size: min.map((value, i) => max[i] - value) };
}

function transformPoint(matrix, x, y, z) {
    return [
        matrix[0] * x + matrix[4] * y + matrix[8] * z + matrix[12],
        matrix[1] * x + matrix[5] * y + matrix[9] * z + matrix[13],
        matrix[2] * x + matrix[6] * y + matrix[10] * z + matrix[14],
    ];
}

function transformBox(matrix, box) {
    const corners = [];
    for (const x of [box.min[0], box.max[0]]) {
        for (const y of [box.min[1], box.max[1]]) {
            for (const z of [box.min[2], box.max[2]]) corners.push(transformPoint(matrix, x, y, z));
        }
    }
    const min = [Infinity, Infinity, Infinity];
    const max = [-Infinity, -Infinity, -Infinity];
    corners.forEach((point) => {
        for (let i = 0; i < 3; i += 1) {
            min[i] = Math.min(min[i], point[i]);
            max[i] = Math.max(max[i], point[i]);
        }
    });
    return { name: box.name, min, max };
}

function mul(a, b) {
    const out = new Array(16).fill(0);
    for (let col = 0; col < 4; col += 1) {
        for (let row = 0; row < 4; row += 1) {
            out[col * 4 + row] = a[row] * b[col * 4] + a[4 + row] * b[col * 4 + 1] + a[8 + row] * b[col * 4 + 2] + a[12 + row] * b[col * 4 + 3];
        }
    }
    return out;
}

function translation(x, y, z) {
    return [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, x, y, z, 1];
}

const rotY180 = [-1, 0, 0, 0, 0, 1, 0, 0, 0, 0, -1, 0, 0, 0, 0, 1];
const zUpToYUp = [0.001, 0, 0, 0, 0, 0, -0.001, 0, 0, 0.001, 0, 0, 0, 0, 0, 1];

function det3(matrix) {
    const a = matrix[0];
    const b = matrix[4];
    const c = matrix[8];
    const d = matrix[1];
    const e = matrix[5];
    const f = matrix[9];
    const g = matrix[2];
    const h = matrix[6];
    const i = matrix[10];
    return a * (e * i - f * h) - b * (d * i - f * g) + c * (d * h - e * g);
}

function categoryOf(id) {
    if (id.startsWith('tek')) return 'tek';
    if (id.startsWith('cift')) return 'cift';
    return 'kose';
}

function targetOf(category) {
    if (category === 'tek') return [0.45, 2.5, 0.6];
    if (category === 'cift') return [0.9, 2.5, 0.6];
    return [0.9, 2.5, 0.9];
}

function displayName(id) {
    const version = id.match(/(\d+)$/)?.[1];
    if (id.startsWith('tek')) return `Tek kapılı V${version}`;
    if (id.startsWith('cift')) return `Çift kapılı V${version}`;
    return `Köşe V${version}`;
}

function findBack(boxes) {
    return boxes.find((box) => /arkalik|back_wall/i.test(box.name)) || null;
}

function findFront(boxes) {
    return boxes.find((box) => /cekmece_on|front_left_end/i.test(box.name)) || null;
}

function center(box) {
    return box.min.map((value, i) => (value + box.max[i]) / 2);
}

function chooseMatrix(raw, boxes) {
    const back = findBack(boxes);
    if (!back) return { error: 'Arkalık parçası yok' };
    const [sx, sy, sz] = raw.size;
    if (sy > 2 && sy < 3 && sx < 2 && sz < 2) {
        const backZ = center(back)[2];
        if (backZ < raw.size[2] * 0.5) return { error: 'Y-up modelde arkalık beklenen yüzde değil' };
        return { matrix: rotY180, sourceUnit: 'm', sourceUpAxis: 'Y', note: 'Ön yüz -Z; Y etrafında 180 derece' };
    }
    if (sz > 2000 && sz < 3000 && sy < 2000 && sx < 2000) {
        const backY = center(back)[1];
        if (backY < -80) return { error: 'Z-up modelde arkalık Y=0 tarafında değil' };
        return { matrix: zUpToYUp, sourceUnit: 'mm', sourceUpAxis: 'Z', note: '0.001 ölçek ve X etrafında -90 derece' };
    }
    return { error: `Eksen tanınmadı ${sx.toFixed(2)}×${sy.toFixed(2)}×${sz.toFixed(2)}` };
}

function covers(box, x0, x1, z0, z1) {
    const overlapX = Math.min(box.max[0], x1) - Math.max(box.min[0], x0);
    const overlapZ = Math.min(box.max[2], z1) - Math.max(box.min[2], z0);
    return overlapX > 0.12 && overlapZ > 0.12;
}

function quadrantHits(boxes) {
    const zones = {
        bl: [0.02, 0.38, 0.02, 0.38],
        br: [0.52, 0.88, 0.02, 0.38],
        fl: [0.02, 0.38, 0.52, 0.88],
        fr: [0.52, 0.88, 0.52, 0.88],
    };
    const hit = { bl: false, br: false, fl: false, fr: false };
    boxes.forEach((box) => {
        Object.entries(zones).forEach(([key, zone]) => {
            if (covers(box, zone[0], zone[1], zone[2], zone[3])) hit[key] = true;
        });
    });
    return hit;
}

fs.mkdirSync(outDir, { recursive: true });
const modules = [];
const report = [];

for (const id of ids) {
    const file = path.join(sourceDir, id, 'model.glb');
    const metaFile = path.join(sourceDir, id, 'module.json');
    const meta = JSON.parse(fs.readFileSync(metaFile, 'utf8'));
    const { json, bin } = readGlb(file);
    const boxes = meshBoxes(json);
    const raw = union(boxes);
    const choice = chooseMatrix(raw, boxes);
    const row = {
        id,
        name: displayName(id),
        opens: true,
        scale: false,
        height: false,
        front: false,
        ground: false,
        shelves: 0,
        drawers: 0,
        selectable: true,
        duplicate: id.startsWith('kose') ? 'sol-sag' : 'evet',
        overlap: 'yerleşim',
        corner: id.startsWith('kose') ? false : 'düz',
        error: choice.error || '',
    };
    if (choice.error) {
        report.push(row);
        continue;
    }
    const moved = boxes.map((box) => transformBox(choice.matrix, box));
    const movedUnion = union(moved);
    const shift = translation(-movedUnion.min[0], -movedUnion.min[1], -movedUnion.min[2]);
    const matrix = mul(shift, choice.matrix);
    if (!(det3(matrix) > 0)) {
        row.error = 'Negatif ölçek';
        report.push(row);
        continue;
    }
    const finalBoxes = boxes.map((box) => transformBox(matrix, box));
    const finalUnion = union(finalBoxes);
    const category = categoryOf(id);
    const target = targetOf(category);
    const size = finalUnion.size;
    const close = (value, expected) => Math.abs(value - expected) <= 0.03;
    row.scale = close(size[0], target[0]) && close(size[1], target[1]) && close(size[2], target[2]);
    row.height = close(size[1], 2.5);
    row.ground = Math.abs(finalUnion.min[1]) <= 0.01 && Math.abs(finalUnion.min[0]) <= 0.01 && Math.abs(finalUnion.min[2]) <= 0.01;
    const back = findBack(finalBoxes);
    const front = findFront(finalBoxes);
    const backZ = back ? center(back)[2] : 1;
    const frontZ = front ? center(front)[2] : size[2] * 0.9;
    row.front = backZ < 0.08 && frontZ > size[2] * 0.55;
    row.shelves = finalBoxes.filter((box) => /raf|shelf/i.test(box.name)).length;
    row.drawers = finalBoxes.filter((box) => /cekmece|drawer/i.test(box.name)).length;
    if (category === 'kose') {
        const hit = quadrantHits(finalBoxes);
        row.corner = hit.bl && hit.br && hit.fl && !hit.fr;
    }
    const outJson = structuredClone(json);
    const world = outJson.nodes.find((node) => node.name === 'world') || outJson.nodes[0];
    delete world.translation;
    delete world.rotation;
    delete world.scale;
    world.matrix = matrix.map((value) => Math.round(value * 1e9) / 1e9);
    const outFile = path.join(outDir, `${id}.glb`);
    writeGlb(outFile, outJson, bin);
    const check = readGlb(outFile);
    if (!check.json.nodes.some((node) => node.matrix)) row.error = 'Normalize düğümü yazılamadı';
    modules.push({
        id,
        name: displayName(id),
        category,
        spec: meta.description || meta.module || displayName(id),
        width: target[0],
        height: target[1],
        depth: target[2],
        modelPath: `/catalog/meemare25-norm/${id}.glb`,
        previewPath: `/catalog/meemare25/${id}/preview.png`,
        sourceUnit: choice.sourceUnit,
        sourceUpAxis: choice.sourceUpAxis,
        normalized: true,
        version: 1,
        shelves: row.shelves,
        drawers: row.drawers,
        cornerShape: row.corner,
    });
    row.size = size.map((value) => Number(value.toFixed(3)));
    report.push(row);
}

fs.writeFileSync(catalogPath, `${JSON.stringify({ version: 1, units: 'm', upAxis: 'Y', frontAxis: '+Z', modules }, null, 2)}\n`);
const failed = report.filter((row) => row.error || !row.scale || !row.height || !row.front || !row.ground || row.corner === false);
console.log(JSON.stringify({ count: report.length, failed: failed.map((row) => row.id), report }, null, 2));
if (failed.length) process.exitCode = 1;
