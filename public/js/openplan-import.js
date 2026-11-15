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

    async function importJsonFile(file) {
        const data = JSON.parse(await file.text());
        const walls = roomBoundsFromProject(data);
        if (!walls) {
            alert('Planda duvar bulunamadı.');
            return;
        }
        document.querySelectorAll('[name="onduvar"], [name="solduvar"], [name="sagduvar"]').forEach((field) => {
            if (field.name === 'onduvar') field.value = walls.frontCm;
            if (field.name === 'solduvar') field.value = walls.leftCm;
            if (field.name === 'sagduvar') field.value = walls.rightCm;
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
})();