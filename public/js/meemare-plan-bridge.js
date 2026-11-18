(function () {
    const HANDOFF_KEY = 'meemare-room-handoff';

    function saveHandoff(payload) {
        const walls = payload?.walls || payload;
        if (!walls) return;
        const front = Number(walls.front ?? walls.onduvar ?? walls.frontCm);
        const left = Number(walls.left ?? walls.solduvar ?? walls.leftCm);
        const right = Number(walls.right ?? walls.sagduvar ?? walls.rightCm);
        const height = Number(walls.height ?? walls.roomHeight ?? walls.heightCm);
        if (![front, left, right].some((n) => Number.isFinite(n) && n >= 80)) return;
        const data = {
            version: 2,
            source: payload.source || 'plan-bridge',
            savedAt: Date.now(),
            walls: {
                frontCm: front,
                leftCm: left,
                rightCm: right,
                heightCm: Number.isFinite(height) && height >= 160 ? height : 320,
            },
            openings: Array.isArray(payload.openings) ? payload.openings : [],
            sketchWalls: Array.isArray(payload.sketchWalls) ? payload.sketchWalls : [],
        };
        if (typeof window.MeemareStudioApplyWalls === 'function') {
            if (window.MeemareStudioApplyWalls(data.walls, {
                openings: data.openings,
                sketchWalls: data.sketchWalls,
            })) {
                return;
            }
        }
        try {
            localStorage.setItem(HANDOFF_KEY, JSON.stringify(data));
        } catch (e) {
            console.warn('meemare handoff', e);
        }
        flash(data.openings.length
            ? `Ölçüler + ${data.openings.length} açıklık kaydedildi. Dolap projenizi açın.`
            : 'Ölçüler kaydedildi. Dolap projenizi açın veya sayfayı yenileyin.');
    }

    function flash(text) {
        let node = document.getElementById('meemare-handoff-toast');
        if (!node) {
            node = document.createElement('div');
            node.id = 'meemare-handoff-toast';
            node.style.cssText =
                'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:99;background:#1f6b4a;color:#fff;padding:10px 16px;border-radius:999px;font:600 12px Montserrat,system-ui,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.15)';
            document.body.appendChild(node);
        }
        node.textContent = text;
        node.style.display = 'block';
        clearTimeout(node._t);
        node._t = setTimeout(() => {
            node.style.display = 'none';
        }, 4200);
    }

    window.addEventListener('message', (event) => {
        const data = event.data;
        if (!data || typeof data !== 'object') return;
        if (data.source === 'meemare-export' && data.walls) {
            saveHandoff(data);
            return;
        }
        if (data.source === 'openplan3d' && data.export && data.walls) {
            saveHandoff({
                source: 'openplan3d',
                walls: data.walls,
                openings: data.openings,
                sketchWalls: data.sketchWalls,
            });
        }
    });

    const form = document.getElementById('plan-handoff');
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            // Önce iframe içindeki OpenPlan’dan tam oda (kapı/pencere) iste.
            const frame = document.querySelector('iframe');
            try {
                frame?.contentWindow?.postMessage({ source: 'meemare', action: 'exportRoom' }, '*');
                // Form ölçüleri yedek; OpenPlan yanıt vermezse yine kaydedilir.
                setTimeout(() => {
                    if (!localStorage.getItem(HANDOFF_KEY)) {
                        saveHandoff({
                            source: 'plan-form',
                            walls: {
                                front: Number(form.onduvar?.value),
                                left: Number(form.solduvar?.value),
                                right: Number(form.sagduvar?.value),
                            },
                        });
                    }
                }, 350);
            } catch {
                saveHandoff({
                    source: 'plan-form',
                    walls: {
                        front: Number(form.onduvar?.value),
                        left: Number(form.solduvar?.value),
                        right: Number(form.sagduvar?.value),
                    },
                });
            }
        });
    }

    window.MeemarePlanBridge = { saveHandoff, HANDOFF_KEY };
})();
