(function () {
    function wallLength(wall) {
        if (Number.isFinite(wall?.x1)) {
            return Math.hypot((wall.x2 || 0) - wall.x1, (wall.y2 || 0) - wall.y1);
        }
        const dx = (wall.end?.x ?? 0) - (wall.start?.x ?? 0);
        const dy = (wall.end?.y ?? 0) - (wall.start?.y ?? 0);
        return Math.hypot(dx, dy);
    }

    function projectFloor(data) {
        return data?.floors?.[0] || data?.floor || null;
    }

    function floorWalls(data) {
        const floor = projectFloor(data);
        return floor?.walls || data?.walls || [];
    }

    function roleMap(walls) {
        const ranked = walls
            .map((w) => ({ w, length: wallLength(w) }))
            .filter((x) => x.length > 40)
            .sort((a, b) => b.length - a.length);
        const midX = (w) => {
            if (Number.isFinite(w.x1)) return (w.x1 + w.x2) / 2;
            return ((w.start?.x || 0) + (w.end?.x || 0)) / 2;
        };
        const isHoriz = (w) => {
            if (Number.isFinite(w.x1)) return Math.abs(w.y2 - w.y1) < Math.abs(w.x2 - w.x1);
            return Math.abs((w.end?.y || 0) - (w.start?.y || 0)) < Math.abs((w.end?.x || 0) - (w.start?.x || 0));
        };
        const horiz = ranked.filter((x) => isHoriz(x.w));
        const vert = ranked.filter((x) => !isHoriz(x.w));
        const front = horiz[0]?.w;
        const left = [...vert].sort((a, b) => midX(a.w) - midX(b.w))[0]?.w;
        const right = [...vert].sort((a, b) => midX(b.w) - midX(a.w))[0]?.w;
        const map = new Map();
        if (front) map.set(front.id, 'front');
        if (left) map.set(left.id, 'left');
        if (right) map.set(right.id, 'right');
        return { map, front, left, right, ranked };
    }

    function openingsFromFloor(data, walls, roles) {
        const floor = projectFloor(data) || {};
        const doors = floor.doors || data.doors || [];
        const windows = floor.windows || data.windows || [];
        const wallById = new Map(walls.map((w) => [w.id, w]));
        const mapOne = (item, kind) => {
            const wall = wallById.get(item.wallId);
            const len = wall ? wallLength(wall) : 0;
            const width = Math.round(Number(item.width) || (kind === 'window' ? 120 : 90));
            const position = Number(item.position);
            const offset = Number.isFinite(position)
                ? Math.max(0, Math.round(position * len - width / 2))
                : Math.round(Number(item.offset) || 0);
            return {
                id: item.id || `${kind}-${Math.random().toString(36).slice(2, 8)}`,
                wallId: roles.map.get(item.wallId) || item.wallId,
                kind,
                style: item.type || item.style || (kind === 'window' ? 'fixed' : 'single'),
                offset,
                width,
                height: Math.round(Number(item.height) || (kind === 'window' ? 120 : 210)),
                sill: Math.round(Number(item.sillHeight ?? item.sill) || (kind === 'window' ? 90 : 0)),
            };
        };
        return [
            ...doors.map((d) => mapOne(d, 'door')),
            ...windows.map((w) => mapOne(w, 'window')),
        ];
    }

    function sketchWallsFrom(walls, roles) {
        return walls.map((w, i) => {
            if (Number.isFinite(w.x1)) {
                return {
                    id: w.id || `w${i}`,
                    role: roles.map.get(w.id) || w.role || 'other',
                    x1: w.x1,
                    y1: w.y1,
                    x2: w.x2,
                    y2: w.y2,
                };
            }
            return {
                id: w.id || `w${i}`,
                role: roles.map.get(w.id) || 'other',
                x1: w.start?.x || 0,
                y1: w.start?.y || 0,
                x2: w.end?.x || 0,
                y2: w.end?.y || 0,
            };
        });
    }

    function roomBoundsFromProject(data) {
        const walls = floorWalls(data);
        if (!walls.length) return null;
        const roles = roleMap(walls);
        const frontLen = roles.front ? wallLength(roles.front) : roles.ranked[0]?.length;
        const leftLen = roles.left ? wallLength(roles.left) : roles.ranked[1]?.length;
        const rightLen = roles.right ? wallLength(roles.right) : leftLen;
        if (![frontLen, leftLen, rightLen].some((n) => Number.isFinite(n) && n > 40)) return null;
        const height = Math.round(
            roles.front?.height || roles.left?.height || walls[0]?.height || 280,
        );
        return {
            frontCm: Math.round(frontLen || 300),
            leftCm: Math.round(leftLen || frontLen || 300),
            rightCm: Math.round(rightLen || leftLen || 300),
            heightCm: height,
            openings: openingsFromFloor(data, walls, roles),
            sketchWalls: sketchWallsFrom(walls, roles),
        };
    }

    function saveHandoff(payload) {
        if (!window.MeemarePlanBridge?.saveHandoff) return;
        window.MeemarePlanBridge.saveHandoff(payload);
    }

    async function importJsonFile(file) {
        const data = JSON.parse(await file.text());
        const room = roomBoundsFromProject(data);
        if (!room) {
            alert('Planda duvar bulunamadı.');
            return;
        }
        document.querySelectorAll('[name="onduvar"], [name="solduvar"], [name="sagduvar"]').forEach((field) => {
            if (field.name === 'onduvar') field.value = room.frontCm;
            if (field.name === 'solduvar') field.value = room.leftCm;
            if (field.name === 'sagduvar') field.value = room.rightCm;
        });
        document.querySelectorAll('[name="odaboy"]').forEach((field) => {
            field.value = room.heightCm;
        });
        saveHandoff({
            source: 'openplan-json',
            walls: {
                frontCm: room.frontCm,
                leftCm: room.leftCm,
                rightCm: room.rightCm,
                heightCm: room.heightCm,
            },
            openings: room.openings,
            sketchWalls: room.sketchWalls,
        });
    }

    ['openplan-json-file', 'studio-plan-json', 'studio-plan-first-json'].forEach((id) => {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            try {
                await importJsonFile(file);
            } catch {
                alert('Geçersiz OpenPlan JSON.');
            }
            input.value = '';
        });
    });

    window.MeemareOpenPlanImport = { roomBoundsFromProject };
})();
