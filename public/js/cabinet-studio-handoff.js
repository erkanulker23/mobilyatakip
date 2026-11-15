(function () {
    const params = new URLSearchParams(location.search);
    const handoff = () => {
        try {
            return JSON.parse(localStorage.getItem('meemare-cabinet-handoff') || 'null');
        } catch {
            return null;
        }
    };

    const num = (key, alt) => {
        const v = Number(params.get(key) ?? params.get(alt));
        return Number.isFinite(v) && v > 0 ? v : null;
    };

    const targets = {
        widthMm: num('genislik', 'w') || handoff()?.module?.width * 10,
        heightMm: num('yukseklik', 'h'),
        depthMm: num('derinlik', 'd'),
        doors: num('kapak', 'doors'),
    };

    if (!targets.widthMm && !targets.heightMm && !targets.depthMm) return;

    function labels(el) {
        const bits = [];
        const id = el.id;
        if (id) {
            const label = document.querySelector(`label[for="${id}"]`);
            if (label) bits.push(label.textContent || '');
        }
        const parent = el.closest('label, .field, .form-group, div');
        if (parent) bits.push(parent.textContent || '');
        bits.push(el.getAttribute('aria-label') || '');
        bits.push(el.placeholder || '');
        bits.push(el.name || '');
        return bits.join(' ').toLowerCase();
    }

    function setValue(patterns, value, unitScale) {
        if (!value) return false;
        const inputs = [...document.querySelectorAll('input[type="number"], input[type="text"]')];
        const scaled = unitScale === 'cm' ? value / 10 : unitScale === 'm' ? value / 1000 : value;
        for (const input of inputs) {
            const text = labels(input);
            if (!patterns.some((p) => p.test(text))) continue;
            input.focus();
            input.value = String(Math.round(scaled));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        }
        return false;
    }

    function pickMetric() {
        const buttons = [...document.querySelectorAll('button, select option, label, span')];
        const hit = buttons.find((n) => /metric.*mm|metrik/i.test(n.textContent || ''));
        if (hit && hit.tagName === 'BUTTON') hit.click();
    }

    function applyOnce() {
        pickMetric();
        setValue([/genişlik/, /width/, /\bw\b/], targets.widthMm, 'mm');
        setValue([/yükseklik/, /height/, /\bh\b/], targets.heightMm, 'mm');
        setValue([/derinlik/, /depth/, /\bd\b/], targets.depthMm, 'mm');
        if (targets.doors === 1) {
            [...document.querySelectorAll('button, option, label')].find((n) => /1 kapak|1 door/i.test(n.textContent || ''))?.click();
        } else if (targets.doors === 2) {
            [...document.querySelectorAll('button, option, label')].find((n) => /2 kapak|2 door/i.test(n.textContent || ''))?.click();
        }
        try {
            localStorage.removeItem('meemare-cabinet-handoff');
        } catch {
            /* */
        }
    }

    const tick = () => {
        applyOnce();
        setTimeout(applyOnce, 800);
        setTimeout(applyOnce, 2400);
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', tick);
    else tick();
})();