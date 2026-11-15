(function () {
    function wallLength(wall) {
        const dx = (wall.end?.x ?? 0) - (wall.start?.x ?? 0);
        const dy = (wall.end?.y ?? 0) - (wall.start?.y ?? 0);
        return Math.hypot(dx, dy);
    }

    function roomBoundsFromProject(data) {
        const floor = data?.floors?.[0];
        const walls = floor?.walls || data?.walls || [];
        if (!walls.length) return null;
        const lengths = walls.map(wallLength).filter((n) => n > 40).sort((a, b) => b - a);
        if (!lengths.length) return null;
        const height = Math.round(walls[0]?.height || 280);
        const front = Math.round(Math.max(lengths[0], lengths[1] || lengths[0]));
        const side = Math.round(lengths.find((n) => Math.abs(n - front) > 20) || lengths[1] || front);
        return { frontCm: front, leftCm: side, rightCm: side, heightCm: height };
    }

    function saveHandoff(walls) {
        if (!window.MeemarePlanBridge?.saveHandoff) return;
        window.MeemarePlanBridge.saveHandoff({ source: 'openplan-json', walls });
    }

    const input = document.getElementById('openplan-json-file');
    if (!input) return;
    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;
        try {
            const data = JSON.parse(await file.text());
            const walls = roomBoundsFromProject(data);
            if (!walls) {
                alert('Planda duvar bulunamadı.');
                return;
            }
            ['onduvar', 'solduvar', 'sagduvar'].forEach((name, i) => {
                const field = document.querySelector(`#plan-handoff [name="${name}"]`);
                if (!field) return;
                field.value = [walls.frontCm, walls.leftCm, walls.rightCm][i];
            });
            saveHandoff({
                source: 'openplan-json',
                walls: {
                    frontCm: walls.frontCm,
                    leftCm: walls.leftCm,
                    rightCm: walls.rightCm,
                    heightCm: walls.heightCm,
                },
            });
        } catch {
            alert('Geçersiz OpenPlan JSON.');
        }
        input.value = '';
    });
})();