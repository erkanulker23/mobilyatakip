(function () {
    const frame = document.querySelector('iframe[title]');
    if (!frame) return;

    const params = new URLSearchParams(location.search);
    const num = (keys, fallback = null) => {
        for (const key of keys) {
            const v = Number(params.get(key));
            if (Number.isFinite(v) && v > 0) return v;
        }
        return fallback;
    };

    function applyIframeQuery({ widthMm, heightMm, depthMm, doors }) {
        const url = new URL(frame.src, location.origin);
        if (widthMm) url.searchParams.set('genislik', String(Math.round(widthMm)));
        if (heightMm) url.searchParams.set('yukseklik', String(Math.round(heightMm)));
        if (depthMm) url.searchParams.set('derinlik', String(Math.round(depthMm)));
        if (doors) url.searchParams.set('kapak', String(doors));
        frame.src = url.pathname + url.search;
    }

    try {
        const roomRaw = localStorage.getItem('meemare-room-handoff');
        if (roomRaw) {
            const data = JSON.parse(roomRaw);
            const w = data?.walls || {};
            const front = Number(w.frontCm ?? w.front);
            const left = Number(w.leftCm ?? w.left);
            const height = Number(w.heightCm ?? w.height) || 320;
            if (front >= 80) {
                applyIframeQuery({
                    widthMm: front * 10,
                    heightMm: height * 10,
                    depthMm: 560,
                    doors: 2,
                });
                localStorage.removeItem('meemare-room-handoff');
            }
        }
    } catch {
        /* */
    }

    const frontCm = num(['onduvar', 'wall', 'on']);
    const leftCm = num(['solduvar', 'left']);
    const heightCm = num(['odaboy', 'roomHeight', 'tavan'], 260);
    if (frontCm) {
        applyIframeQuery({
            widthMm: frontCm * 10,
            heightMm: heightCm * 10,
            depthMm: num(['derinlik', 'd'], 600),
            doors: num(['kapak', 'doors'], 2),
        });
    } else if (num(['genislik', 'w'])) {
        applyIframeQuery({
            widthMm: num(['genislik', 'w']),
            heightMm: num(['yukseklik', 'h'], 2600),
            depthMm: num(['derinlik', 'd'], 560),
            doors: num(['kapak', 'doors'], 2),
        });
    }

    const token = params.get('proje');
    const record = params.get('kayit');
    if (!token && !record) return;

    const path = record
        ? `/dolap/proje/kayit/${record}`
        : `/dolap/proje/${token}`;

    fetch(path, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => (r.ok ? r.json() : null))
        .then((data) => {
            if (!data?.design) return;
            const d = data.design;
            const mod = (d.modules || []).find((m) => m.kind !== 'corner') || d.modules?.[0];
            const widthMm = mod?.width ? Math.round(Number(mod.width) * 10) : Math.round((d.wallWidth || 300) * 10);
            const heightMm = mod ? Math.round((mod.height || d.height || 250) * 10) : Math.round((d.height || d.roomHeight || 260) * 10);
            const depthMm = Math.round((mod?.glbDepth || mod?.depth || d.depth || 60) * 10);
            const doors = mod?.door === 'solid' ? 1 : mod?.door === 'double' ? 2 : 2;
            applyIframeQuery({ widthMm, heightMm, depthMm, doors });
            try {
                localStorage.setItem(
                    'meemare-cabinet-handoff',
                    JSON.stringify({
                        savedAt: Date.now(),
                        projectName: data.projectName || d.name,
                        module: mod,
                        room: {
                            frontCm: d.wallWidth,
                            leftCm: d.leftWall,
                            rightCm: d.rightWall,
                            heightCm: d.roomHeight,
                        },
                    }),
                );
            } catch {
                /* */
            }
        })
        .catch(() => {});
})();