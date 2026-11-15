(function () {
    const HANDOFF_KEY = 'meemare-cabinet-handoff';

    function readQuery() {
        const params = new URLSearchParams(location.search);
        const num = (key) => {
            const v = Number(params.get(key));
            return Number.isFinite(v) && v > 0 ? v : null;
        };
        return {
            widthMm: num('genislik') || num('w'),
            heightMm: num('yukseklik') || num('h'),
            depthMm: num('derinlik') || num('d'),
            doors: num('kapak'),
        };
    }

    function banner(text) {
        const bar = document.querySelector('.bar');
        if (!bar || document.getElementById('cabinet-handoff-hint')) return;
        const hint = document.createElement('p');
        hint.id = 'cabinet-handoff-hint';
        hint.style.cssText = 'margin:0;padding:8px 14px;background:#eef6f1;border-bottom:1px solid #d4e8dc;font-size:12px;color:#3d5247';
        hint.textContent = text;
        bar.insertAdjacentElement('afterend', hint);
    }

    try {
        const raw = localStorage.getItem(HANDOFF_KEY);
        const q = readQuery();
        if (raw) {
            const data = JSON.parse(raw);
            const mod = data?.module;
            const label = mod?.name || mod?.glb || 'Modül';
            const w = q.widthMm || (mod?.width ? Math.round(mod.width * 10) : null);
            const parts = [label, w ? `${w} mm genişlik` : ''].filter(Boolean).join(' · ');
            banner(`Meemare stüdyodan: ${parts}. Cabinet Studio içinde ölçüleri elle girin veya içe aktarın.`);
            localStorage.removeItem(HANDOFF_KEY);
        } else if (q.widthMm && q.heightMm && q.depthMm) {
            banner(`Önerilen ölçü: ${q.widthMm} × ${q.heightMm} × ${q.depthMm} mm${q.doors ? `, ${q.doors} kapak` : ''}.`);
        }
    } catch (e) {
        console.warn('cabinet bridge', e);
    }
})();