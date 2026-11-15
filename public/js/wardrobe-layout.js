/** Duvar dizilimi. Three.js yok; stüdyo ve node testi aynı fonksiyonu çağırır. */

export function alongSize(mod) {
    if (mod?.kind === 'end') return 2;
    return Math.max(30, Number(mod?.width) || 45);
}

export function wallName(mod) {
    if (mod?.kind === 'corner') return 'front';
    if (mod?.wall === 'left' || mod?.wall === 'right') return mod.wall;
    return 'front';
}

function depthNudge(mod) {
    const n = Number(mod?.depthOffset) || 0;
    return Math.min(120, Math.max(0, n));
}

function openingBlocks(openings, wallId, margin = 10) {
    return (openings || [])
        .filter((item) => item.wallId === wallId)
        .map((item) => ({
            start: Math.max(0, (Number(item.offset) || 0) - margin),
            end: (Number(item.offset) || 0) + (Number(item.width) || 0) + margin,
        }));
}

function hitsOpening(openings, wallId, at, along) {
    const end = at + along;
    return openingBlocks(openings, wallId).some((block) => at < block.end - 0.5 && end > block.start + 0.5);
}

export function wallOffsetMin(mod, modules) {
    const wall = wallName(mod);
    if (wall === 'right') {
        const rightCorner = modules.find((item) => item !== mod && item.kind === 'corner' && item.cornerSide !== 'left');
        return rightCorner ? alongSize(rightCorner) : 0;
    }
    const leftCorner = modules.find((item) => item !== mod && item.kind === 'corner' && item.cornerSide === 'left');
    return leftCorner ? alongSize(leftCorner) : 0;
}

export function wallOffsetMax(mod, modules, limits) {
    const along = alongSize(mod);
    const wall = wallName(mod);
    const rightCorner = modules.find((item) => item !== mod && item.kind === 'corner' && item.cornerSide !== 'left');
    const rightHold = rightCorner ? alongSize(rightCorner) : 0;
    if (wall === 'front') return Math.max(0, limits.front - rightHold - along);
    const side = wall === 'left' ? limits.left : limits.right;
    return Math.max(0, side - along);
}

function ensurePlacement(modules) {
    const cursor = { left: 0, front: 0, right: 0 };
    modules.forEach((mod) => {
        if (mod.kind === 'corner' && mod.cornerSide !== 'left') mod.cornerSide = 'right';
        if (mod.wall !== 'left' && mod.wall !== 'right') mod.wall = 'front';
        if (!Number.isFinite(Number(mod.offset))) {
            const wall = wallName(mod);
            mod.offset = cursor[wall];
            cursor[wall] += alongSize(mod);
        }
    });
}

/**
 * @param {object[]} modules
 * @param {{front:number,left:number,right:number,depth?:number,openings?:object[],dragId?:string|null,owned?:object[],magneticSnap?:Function}} ctx
 */
export function layoutModules(modules, ctx) {
    ensurePlacement(modules);
    const front = ctx.front;
    const leftLen = ctx.left;
    const rightLen = ctx.right;
    const openings = ctx.openings || [];
    const dragId = ctx.dragId || null;
    const owned = ctx.owned || modules;
    const magneticSnap = ctx.magneticSnap || ((mod, at) => at);
    const leftCorner = modules.find((mod) => mod.kind === 'corner' && mod.cornerSide === 'left');
    const rightCorner = modules.find((mod) => mod.kind === 'corner' && mod.cornerSide !== 'left');
    if (modules.filter((mod) => mod.kind === 'corner' && mod.cornerSide === 'left').length > 1) return null;
    if (modules.filter((mod) => mod.kind === 'corner' && mod.cornerSide !== 'left').length > 1) return null;
    const leftHold = leftCorner ? alongSize(leftCorner) : 0;
    const rightHold = rightCorner ? alongSize(rightCorner) : 0;
    if (leftHold + rightHold > front + 0.05) return null;
    const spots = [];
    if (leftCorner) spots.push({ mod: leftCorner, x: 0, z: 0, rot: 0 });
    if (rightCorner) spots.push({ mod: rightCorner, x: front - rightHold, z: 0, rot: 0 });
    const pack = (wall, begin, limit) => {
        const list = modules
            .filter((mod) => mod.kind !== 'corner' && wallName(mod) === wall)
            .sort((a, b) => Number(a.offset) - Number(b.offset));
        const occupied = [];
        const fits = (at, along) => at >= begin - 0.1
            && at + along <= limit + 0.05
            && !hitsOpening(openings, wall, at, along)
            && !occupied.some((seg) => at < seg.end - 0.4 && at + along > seg.start + 0.4);
        const placeOne = (mod) => {
            const along = alongSize(mod);
            const wanted = Number(mod.offset);
            let at = Number.isFinite(wanted) ? wanted : begin;
            at = Math.max(begin, Math.min(at, Math.max(begin, limit - along)));
            if (Math.abs(at - wanted) > 0.05) mod.offset = at;
            if (!fits(at, along)) {
                if (!dragId) {
                    let best = null;
                    for (let cursor = begin; cursor + along <= limit + 0.05; cursor += 1) {
                        if (!fits(cursor, along)) continue;
                        if (best === null || Math.abs(cursor - at) < Math.abs(best - at)) best = cursor;
                    }
                    if (best === null) return null;
                    at = best;
                }
            }
            const movable = mod.id === dragId || !owned.includes(mod);
            if (movable && !dragId) {
                occupied.forEach((seg) => {
                    if (at >= seg.end && at - seg.end < 4 && fits(seg.end, along)) at = seg.end;
                    const before = seg.start - along;
                    if (at > begin && at + along <= seg.start && seg.start - (at + along) < 4 && fits(before, along)) at = before;
                });
                if (at - begin < 4 && fits(begin, along)) at = begin;
                mod.offset = at;
            } else if (mod.id === dragId) {
                mod.offset = magneticSnap(mod, at);
            }
            occupied.push({ start: at, end: at + along });
            if (!dragId || mod.id === dragId) mod.offset = at;
            const nudge = depthNudge(mod);
            if (wall === 'front') spots.push({ mod, x: at, z: nudge, rot: 0 });
            else if (wall === 'left') spots.push({ mod, x: nudge, z: at + along, rot: Math.PI / 2 });
            else spots.push({ mod, x: front - nudge, z: at, rot: -Math.PI / 2 });
            return occupied;
        };
        const dragged = list.find((mod) => mod.id === dragId);
        if (dragged && !placeOne(dragged)) return null;
        for (const mod of list) {
            if (mod === dragged) continue;
            if (!placeOne(mod)) return null;
        }
        return occupied;
    };
    const savedOffsets = modules.map((mod) => mod.offset);
    const revert = () => {
        modules.forEach((mod, index) => { mod.offset = savedOffsets[index]; });
        return null;
    };
    const runOf = (wall) => modules.filter((mod) => mod.kind !== 'corner' && wallName(mod) === wall);
    const bodies = (list) => list.filter((mod) => mod.kind !== 'end');
    const nearest = (list) => bodies(list).reduce((best, mod) => (!best || (Number(mod.offset) || 0) < (Number(best.offset) || 0) ? mod : best), null);
    const farthest = (list) => bodies(list).reduce((best, mod) => {
        if (!best) return mod;
        const end = (Number(mod.offset) || 0) + alongSize(mod);
        const bestEnd = (Number(best.offset) || 0) + alongSize(best);
        return end > bestEnd ? mod : best;
    }, null);
    const reach = (mod) => {
        if (!mod) return 0;
        const body = Math.max(30, Number(mod.glbDepth) || Number(ctx.depth) || 60);
        return body + depthNudge(mod);
    };
    const used = (list) => list.reduce((sum, mod) => sum + alongSize(mod), 0);
    const frontList = runOf('front');
    const leftList = runOf('left');
    const rightList = runOf('right');
    const leftCab = nearest(leftList);
    const rightCab = nearest(rightList);
    const frontLeftCab = nearest(frontList);
    const frontRightCab = farthest(frontList);
    const leftDepth = reach(leftCab);
    const rightDepth = reach(rightCab);
    const frontLeftDepth = reach(frontLeftCab);
    const frontRightDepth = reach(frontRightCab);
    const leftMeets = !leftCorner && leftCab && frontLeftCab
        && (Number(leftCab.offset) || 0) <= frontLeftDepth + 0.6
        && (Number(frontLeftCab.offset) || 0) <= leftDepth + 0.6;
    const rightGap = frontRightCab ? front - ((Number(frontRightCab.offset) || 0) + alongSize(frontRightCab)) : Infinity;
    const rightMeets = !rightCorner && rightCab && frontRightCab
        && (Number(rightCab.offset) || 0) <= frontRightDepth + 0.6
        && rightGap <= rightDepth + 0.6;
    const frontSpan = front - leftHold - rightHold;
    const frontUsed = used(frontList);
    const wantLeft = leftMeets && used(leftList) <= leftLen - frontLeftDepth + 0.05 ? leftDepth : 0;
    const wantRight = rightMeets && used(rightList) <= rightLen - frontRightDepth + 0.05 ? rightDepth : 0;
    let frontBegin = leftHold;
    let frontLimit = front - rightHold;
    if (wantLeft && wantRight && frontUsed + wantLeft + wantRight <= frontSpan + 0.05) {
        frontBegin += wantLeft;
        frontLimit -= wantRight;
    } else if (wantLeft && frontUsed + wantLeft <= frontSpan + 0.05) frontBegin += wantLeft;
    else if (wantRight && frontUsed + wantRight <= frontSpan + 0.05) frontLimit -= wantRight;
    const leftBegin = leftHold > 0 ? leftHold : (wantLeft ? frontLeftDepth : 0);
    const rightBegin = rightHold > 0 ? rightHold : (wantRight ? frontRightDepth : 0);
    if (!pack('front', frontBegin, frontLimit)) return revert();
    if (!pack('left', leftBegin, leftLen) || !pack('right', rightBegin, rightLen)) return revert();
    return spots;
}
