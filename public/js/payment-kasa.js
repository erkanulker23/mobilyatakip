(function () {
    var CONFIG = {
        nakit: {
            label: 'Nakit Kasası',
            types: ['kasa'],
            help: 'Nakit tahsilat hangi kasaya girecek?',
            empty: 'Aktif nakit kasası yok. Kasa menüsünden «Nakit Kasa» ekleyin.',
        },
        havale: {
            label: 'Banka / Hesap',
            types: ['banka', 'kasa'],
            help: 'Havale hangi hesaba yapıldı?',
            empty: 'Aktif kasa veya banka hesabı yok. Kasa menüsünden hesap ekleyin.',
        },
        kredi_karti: {
            label: 'Banka / POS Hesabı',
            types: ['banka', 'kasa'],
            help: 'Kredi kartı tahsilatı hangi hesaba yansıyacak?',
            empty: 'Aktif kasa veya banka hesabı yok. Kasa menüsünden hesap ekleyin.',
        },
        diger: {
            label: 'Kasa',
            types: [],
            help: '',
            empty: '',
        },
    };

    function requiresKasa(type) {
        return type === 'nakit' || type === 'havale' || type === 'kredi_karti';
    }

    function parseAmount(el) {
        if (!el) return 1;
        if (typeof window.parseMoney === 'function') {
            return window.parseMoney(el.value || '0') || 0;
        }
        var n = parseFloat(String(el.value || '0').replace(/\./g, '').replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    }

    function getPaymentMode(root) {
        if (!root.dataset.paymentModeName) return 'none';
        var checked = document.querySelector('input[name="' + root.dataset.paymentModeName + '"]:checked');
        return checked ? checked.value : 'none';
    }

    function updateField(root) {
        var ptEl = document.getElementById(root.dataset.paymentTypeId);
        var amountEl = root.dataset.amountId ? document.getElementById(root.dataset.amountId) : null;
        var kasaEl = document.getElementById(root.dataset.kasaId);
        var hintEl = root.dataset.hintId ? document.getElementById(root.dataset.hintId) : null;
        var labelEl = root.dataset.labelId ? document.getElementById(root.dataset.labelId) : null;
        var helpEl = root.querySelector('[data-kasa-help]');

        if (!ptEl || !kasaEl) return;

        var pt = ptEl.value || 'diger';
        var cfg = CONFIG[pt] || CONFIG.diger;
        var mode = getPaymentMode(root);
        var amount = parseAmount(amountEl);
        var paymentActive = mode === 'kapora' || mode === 'full';
        var needsKasa = paymentActive && requiresKasa(pt) && (mode === 'full' || amount > 0);
        var showField = paymentActive && requiresKasa(pt);

        root.style.display = showField ? '' : 'none';

        if (labelEl) {
            var star = labelEl.querySelector('[data-kasa-required-star]');
            labelEl.childNodes[0].textContent = cfg.label + ' ';
            if (!star && hintEl) {
                labelEl.appendChild(document.createTextNode(' '));
            }
        }

        var selected = kasaEl.value;
        var visibleCount = 0;
        Array.prototype.forEach.call(kasaEl.options, function (opt, idx) {
            if (idx === 0) {
                opt.hidden = false;
                opt.disabled = false;
                return;
            }
            var kasaType = opt.getAttribute('data-kasa-type') || '';
            var show = cfg.types.length === 0 || cfg.types.indexOf(kasaType) !== -1;
            opt.hidden = !show;
            opt.disabled = !show;
            if (!show && opt.selected) {
                opt.selected = false;
            }
            if (show) visibleCount++;
        });

        if (kasaEl.value) {
            var current = kasaEl.options[kasaEl.selectedIndex];
            if (!current || current.disabled || current.value === '') {
                kasaEl.value = '';
            }
        }
        if (selected && kasaEl.value === '') {
            kasaEl.value = '';
        }

        if (hintEl) {
            hintEl.style.display = needsKasa ? 'inline' : 'none';
        }

        var keepDisabled = kasaEl.getAttribute('data-keep-disabled') === '1';
        if (!keepDisabled) {
            kasaEl.disabled = !showField;
        }
        kasaEl.required = needsKasa && !kasaEl.disabled;

        if (helpEl) {
            helpEl.classList.remove('text-amber-600', 'dark:text-amber-400');
            if (!showField) {
                helpEl.textContent = '';
            } else if (visibleCount === 0) {
                helpEl.textContent = cfg.empty;
                helpEl.classList.add('text-amber-600', 'dark:text-amber-400');
            } else {
                helpEl.textContent = cfg.help;
            }
        }
    }

    function bindField(root) {
        if (root.dataset.kasaBound === '1') {
            updateField(root);
            return;
        }
        root.dataset.kasaBound = '1';

        var ptEl = document.getElementById(root.dataset.paymentTypeId);
        var amountEl = root.dataset.amountId ? document.getElementById(root.dataset.amountId) : null;

        function refresh() {
            updateField(root);
        }

        if (ptEl) ptEl.addEventListener('change', refresh);
        if (amountEl) {
            amountEl.addEventListener('input', refresh);
            amountEl.addEventListener('change', refresh);
        }
        if (root.dataset.paymentModeName) {
            document.querySelectorAll('input[name="' + root.dataset.paymentModeName + '"]').forEach(function (radio) {
                radio.addEventListener('change', refresh);
            });
        }
        refresh();
    }

    function initPaymentKasaFields() {
        document.querySelectorAll('.payment-kasa-field').forEach(bindField);
    }

    document.addEventListener('DOMContentLoaded', initPaymentKasaFields);
    window.initPaymentKasaFields = initPaymentKasaFields;
})();
