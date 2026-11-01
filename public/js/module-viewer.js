import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const FINISHES = [
    ['Lake Beyaz', '#f3f0e8'],
    ['Vizon', '#c4b39a'],
    ['Antrasit', '#3c3c3e'],
    ['Kırmızı', '#8e2f2f'],
    ['Lacivert', '#243e6b'],
];

const REPLACE = [
    ['01_gomlek_beyaz', 316, 'Gömlek 9 parçalı kutu; kumaş kırımı yok'],
    ['02_gomlek_mavi', 316, 'Aynı gömlek kalıbı, yalnız renk değişiyor'],
    ['03_blazer_kahve', 328, 'Ceket gövdesi düz prizma, yaka ve cep yok'],
    ['09_katli_tisort', 276, 'Katlı tişört üst üste üç prizma'],
    ['10_katli_kazak', 276, 'Katlı kazak, tişörtle aynı kalıp'],
    ['13_sneaker_cift', 232, 'Ayakkabı taban ve burun kutularından ibaret'],
];

const HANG = /gomlek|blazer|elbise|trenc|pantolon|etek|ceket|cebket/;

const viewport = document.getElementById('viewport');
const findings = document.getElementById('findings');
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.05;
renderer.shadowMap.enabled = true;
viewport.appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.background = new THREE.Color('#e6e4e0');
const camera = new THREE.PerspectiveCamera(32, 1, 0.05, 40);
camera.position.set(0.7, 1.45, -5.4);
const controls = new OrbitControls(camera, renderer.domElement);
controls.target.set(0.45, 1.15, 0.25);
controls.enableDamping = true;
controls.maxPolarAngle = Math.PI * 0.49;

scene.add(new THREE.AmbientLight(0xffffff, 0.45));
const key = new THREE.DirectionalLight(0xfff6ea, 2.1);
key.position.set(-1.4, 3.2, -2.2);
key.castShadow = true;
scene.add(key);
scene.add(new THREE.DirectionalLight(0xdfe7f2, 0.6).translateX(2).translateY(1.5));

const floor = new THREE.Mesh(
    new THREE.CircleGeometry(3.2, 48),
    new THREE.MeshStandardMaterial({ color: '#d9d3c8', roughness: 0.9 })
);
floor.rotation.x = -Math.PI / 2;
floor.receiveShadow = true;
scene.add(floor);

const cabinet = new THREE.Group();
scene.add(cabinet);

const mats = {
    body: new THREE.MeshStandardMaterial({ color: '#f3f0e8', roughness: 0.72, metalness: 0.02 }),
    door: new THREE.MeshStandardMaterial({ color: '#c4b39a', roughness: 0.55, metalness: 0.02 }),
    drawer: new THREE.MeshStandardMaterial({ color: '#f3f0e8', roughness: 0.62, metalness: 0.02 }),
    back: new THREE.MeshStandardMaterial({ color: '#f7f4ee', roughness: 0.8, metalness: 0 }),
    shelf: new THREE.MeshStandardMaterial({ color: '#f7f4ee', roughness: 0.78, metalness: 0 }),
    metal: new THREE.MeshStandardMaterial({ color: '#c5c8cc', roughness: 0.32, metalness: 0.72 }),
};

const doors = [];
let doorOpen = false;
let accessoriesOn = true;
let spin = null;
const report = [];

function roleOf(name) {
    if (/SOL_YAN|SAG_YAN|UST_TABLA|ALT_TABLA|DIKME|BAZA|CEKMECE_ARKA|CEKMECE_TABAN/.test(name)) return 'body';
    if (/ARKALIK/.test(name)) return 'back';
    if (/^RAF_/.test(name)) return 'shelf';
    if (/CEKMECE_ON/.test(name)) return 'drawer';
    if (/ASKI/.test(name)) return 'metal';
    return null;
}

function boxOf(object) {
    return new THREE.Box3().setFromObject(object);
}

function paint(root) {
    root.traverse((node) => {
        if (!node.isMesh) return;
        node.castShadow = true;
        node.receiveShadow = true;
        const role = roleOf(node.name);
        if (role) node.material = mats[role];
    });
}

function addDoors(bounds) {
    const width = (bounds.max.x - bounds.min.x) / 2 - 0.004;
    const y0 = bounds.min.y + 0.09;
    const height = bounds.max.y - 0.018 - y0;
    const geo = new THREE.BoxGeometry(width, height, 0.018);
    const hinge = (x, direction) => {
        const group = new THREE.Group();
        group.position.set(x, y0, bounds.min.z);
        const leaf = new THREE.Mesh(geo, mats.door);
        leaf.position.set(direction * width / 2, height / 2, -0.01);
        leaf.castShadow = true;
        group.add(leaf);
        group.userData.direction = direction;
        cabinet.add(group);
        doors.push(group);
    };
    hinge(bounds.min.x + 0.004, 1);
    hinge(bounds.max.x - 0.004, -1);
}

function shiftToClear(group, box, limitMin, limitMax, axis) {
    const index = axis === 'x' ? 0 : 2;
    const min = axis === 'x' ? box.min.x : box.min.z;
    const max = axis === 'x' ? box.max.x : box.max.z;
    let delta = 0;
    if (min < limitMin) delta = limitMin - min;
    if (max + delta > limitMax) delta += limitMax - (max + delta);
    if (Math.abs(delta) < 0.001) return 0;
    group.position.setComponent(index, group.position.getComponent(index) + delta);
    return delta;
}

function settle(root) {
    const named = new Map();
    let host = root;
    root.traverse((node) => {
        if (node.name) named.set(node.name, node);
        if (node.name.startsWith('AKSESUAR_') && node.parent) host = node.parent;
    });
    const groups = new Map();
    [...host.children].forEach((node) => {
        const match = node.name.match(/^AKSESUAR_(\d+)_/);
        if (!match) return;
        let group = groups.get(match[1]);
        if (!group) {
            group = new THREE.Group();
            group.name = `aksesuar-${match[1]}`;
            group.userData.hang = HANG.test(node.name);
            group.userData.label = node.name.split('_').slice(3, 6).join('_');
            host.add(group);
            groups.set(match[1], group);
        }
        group.userData.hang = group.userData.hang || HANG.test(node.name);
        group.add(node);
    });

    const sideL = boxOf(named.get('SOL_YAN'));
    const sideR = boxOf(named.get('SAG_YAN'));
    const back = boxOf(named.get('ARKALIK'));
    const innerMinX = sideL.max.x + 0.006;
    const innerMaxX = sideR.min.x - 0.006;
    const innerMinZ = 0.012;
    const innerMaxZ = back.min.z - 0.008;
    const shelves = [...named.values()].filter((node) => /^RAF_|^ALT_TABLA$/.test(node.name)).map((node) => boxOf(node));
    const dikme = [...named.values()].filter((node) => node.name.startsWith('DIKME')).map((node) => boxOf(node));

    let movedInside = 0;
    groups.forEach((group) => {
        let box = boxOf(group);
        const dx = shiftToClear(group, box, innerMinX, innerMaxX, 'x');
        box = boxOf(group);
        const dz = shiftToClear(group, box, innerMinZ, innerMaxZ, 'z');
        if (dx || dz) movedInside += 1;
        if (group.userData.hang) return;
        box = boxOf(group);
        const support = shelves
            .map((shelf) => ({ shelf, gap: box.min.y - shelf.max.y }))
            .filter((item) => item.gap > -0.03 && item.gap < 0.08)
            .filter((item) => box.max.x > item.shelf.min.x + 0.02 && box.min.x < item.shelf.max.x - 0.02)
            .sort((a, b) => Math.abs(a.gap) - Math.abs(b.gap))[0];
        if (!support || Math.abs(support.gap) < 0.002) return;
        group.position.y -= support.gap - 0.001;
        report.push(`${group.userData.label || group.name} rafa oturtuldu (${Math.round(support.gap * 1000)} mm).`);
    });
    if (movedInside) report.push(`${movedInside} aksesuar gövdenin içine alındı; yan panel ve arkalıkla çakışma kesildi.`);

    const bays = [];
    const cuts = [innerMinX, ...dikme.flatMap((box) => [box.min.x - 0.004, box.max.x + 0.004]), innerMaxX].sort((a, b) => a - b);
    for (let i = 0; i < cuts.length - 1; i += 2) bays.push([cuts[i], cuts[i + 1]]);
    const hanging = [...groups.values()].filter((group) => group.userData.hang);
    bays.forEach(([minX, maxX]) => {
        const inside = hanging.filter((group) => {
            const center = boxOf(group).getCenter(new THREE.Vector3()).x;
            return center >= minX && center <= maxX;
        }).sort((a, b) => boxOf(a).min.x - boxOf(b).min.x);
        if (inside.length < 2) return;
        const gap = 0.012;
        const widths = inside.map((group) => {
            const box = boxOf(group);
            return box.max.x - box.min.x;
        });
        const room = maxX - minX;
        const natural = widths.reduce((sum, width) => sum + width, 0) + gap * (inside.length - 1);
        const scale = natural > room ? room / natural : 1;
        let cursor = minX;
        inside.forEach((group, index) => {
            if (scale < 0.999) group.scale.x *= scale;
            const box = boxOf(group);
            group.position.x += cursor - box.min.x;
            cursor += widths[index] * scale + gap;
        });
        if (scale < 0.999) {
            report.push(`Askılıklar ${Math.round(room * 100)} cm göze sığmıyordu; ${inside.length} parça %${Math.round(scale * 100)} genişliğe indirildi.`);
        }
    });

    const size = boxOf(host).getSize(new THREE.Vector3());
    report.unshift(`Gövde ${Math.round(size.x * 100)} × ${Math.round(size.y * 100)} × ${Math.round(size.z * 100)} cm. Ön yüzde kapak kanadı yok; kanatlar görüntüleyicide eklendi.`);
    REPLACE.forEach(([id, tris, why]) => report.push(`${id}: ${tris} üçgen. ${why}.`));
    findings.innerHTML = report.map((line) => `<li>${line}</li>`).join('');
}

function resize() {
    const width = viewport.clientWidth || 600;
    const height = viewport.clientHeight || 400;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
}

function tick() {
    const doorTarget = doorOpen ? 1.15 : 0;
    doors.forEach((door) => {
        const target = doorTarget * door.userData.direction;
        door.rotation.y += (target - door.rotation.y) * 0.12;
    });
    if (spin) {
        spin.t = Math.min(1, spin.t + 0.012);
        const eased = 1 - (1 - spin.t) ** 3;
        cabinet.rotation.y = spin.from + (spin.to - spin.from) * eased;
        if (spin.t === 1) spin = null;
    }
    controls.update();
    renderer.render(scene, camera);
}

function bindFinishes() {
    document.querySelectorAll('[data-role]').forEach((field) => {
        const role = field.dataset.role;
        FINISHES.forEach(([label, color], index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `swatch${index === (role === 'door' ? 1 : 0) ? ' is-on' : ''}`;
            button.innerHTML = `<i style="background:${color}"></i>${label}`;
            button.onclick = () => {
                mats[role].color.set(color);
                field.querySelectorAll('.swatch').forEach((node) => node.classList.remove('is-on'));
                button.classList.add('is-on');
            };
            field.appendChild(button);
        });
    });
}

document.getElementById('btn-doors').onclick = (event) => {
    doorOpen = !doorOpen;
    event.currentTarget.textContent = doorOpen ? 'Kapakları kapat' : 'Kapakları aç';
    event.currentTarget.classList.toggle('is-on', doorOpen);
};
document.getElementById('btn-accessories').onclick = (event) => {
    accessoriesOn = !accessoriesOn;
    cabinet.traverse((node) => {
        if (node.name.startsWith('aksesuar-')) node.visible = accessoriesOn;
    });
    event.currentTarget.textContent = accessoriesOn ? 'Aksesuarları gizle' : 'Aksesuarları göster';
    event.currentTarget.classList.toggle('is-on', accessoriesOn);
};
document.getElementById('btn-spin').onclick = () => {
    spin = { from: cabinet.rotation.y, to: cabinet.rotation.y + Math.PI * 2, t: 0 };
};

bindFinishes();
resize();
window.addEventListener('resize', resize);
renderer.setAnimationLoop(tick);

new GLTFLoader().load(window.MODULE_VIEW.modelUrl, (gltf) => {
    const root = gltf.scene;
    paint(root);
    cabinet.add(root);
    const bounds = boxOf(root);
    addDoors(bounds);
    const center = bounds.getCenter(new THREE.Vector3());
    const size = bounds.getSize(new THREE.Vector3());
    const dist = Math.max(size.x, size.y) / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov) / 2)) * 1.25;
    camera.position.set(center.x + size.x * 0.35, center.y + size.y * 0.08, bounds.min.z - dist);
    controls.target.set(center.x, center.y, center.z);
    controls.update();
    settle(root);
}, undefined, (error) => {
    findings.innerHTML = `<li>Model yüklenemedi: ${error?.message || 'GLB okunamadı'}</li>`;
});
