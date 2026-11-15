import assert from 'node:assert/strict';
import test from 'node:test';
import { layoutModules, wallOffsetMin } from '../../public/js/wardrobe-layout.js';

function cab(id, width, offset = 0, wall = 'front') {
    return { id, kind: 'wardrobe', wall, width, offset, product: 'cift-raf' };
}

function place(front, modules, extra = {}) {
    return layoutModules(modules, {
        front,
        left: extra.left ?? 300,
        right: extra.right ?? 300,
        depth: 60,
        openings: extra.openings || [],
        owned: extra.owned || modules,
    });
}

const WALLS = [180, 225, 270, 315, 360, 413, 440, 550, 785];

test('exact runs sit inside the wall and do not overlap', () => {
    for (const front of WALLS) {
        const count = Math.floor(front / 90);
        const modules = Array.from({ length: count }, (_, i) => cab(`m${i}`, 90, i * 90));
        const spots = place(front, modules);
        assert.ok(spots, `${front} cm should fit ${count} modules`);
        const ends = modules.map((mod) => mod.offset + 90);
        assert.ok(Math.max(...ends) <= front + 0.05, `${front} cm overflow`);
        const sorted = [...modules].sort((a, b) => a.offset - b.offset);
        for (let i = 1; i < sorted.length; i += 1) {
            assert.ok(sorted[i].offset >= sorted[i - 1].offset + 90 - 0.05);
        }
    }
});

test('one extra 90 cm module is refused', () => {
    for (const front of WALLS) {
        const count = Math.floor(front / 90) + 1;
        const modules = Array.from({ length: count }, (_, i) => cab(`x${i}`, 90, i * 90));
        assert.equal(place(front, modules), null, `${front} cm should refuse ${count} modules`);
    }
});

test('a right-wall module cannot start inside the right corner', () => {
    const corner = { id: 'c', kind: 'corner', cornerSide: 'right', width: 90, offset: 0, wall: 'front' };
    const side = cab('s', 60, 0, 'right');
    assert.equal(wallOffsetMin(side, [corner, side]), 90);
    const spots = place(300, [corner, side], { right: 300 });
    assert.ok(spots);
    assert.ok(side.offset >= 90 - 0.05);
});

test('a door opening blocks that span', () => {
    const modules = [cab('door', 90, 40)];
    const blocked = place(300, modules, { openings: [{ wallId: 'front', offset: 20, width: 90 }] });
    assert.ok(blocked);
    const end = modules[0].offset + 90;
    const openStart = 10;
    const openEnd = 120;
    const overlaps = modules[0].offset < openEnd - 0.5 && end > openStart + 0.5;
    assert.equal(overlaps, false);
});

test('two corners on the same side are refused', () => {
    const modules = [
        { id: 'a', kind: 'corner', cornerSide: 'left', width: 90, offset: 0 },
        { id: 'b', kind: 'corner', cornerSide: 'left', width: 90, offset: 0 },
    ];
    assert.equal(place(400, modules), null);
});
