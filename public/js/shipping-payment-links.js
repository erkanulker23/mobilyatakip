(function () {
    const tomSelectOptions = {
        maxOptions: 300,
        searchField: ['text'],
        allowEmptyOption: true,
        plugins: ['remove_button'],
        persist: false,
    };

    function mountTomSelect(elementId, placeholder) {
        if (typeof TomSelect === 'undefined') {
            return null;
        }

        const el = document.getElementById(elementId);
        if (!el) {
            return null;
        }

        if (el.tomselect) {
            el.tomselect.destroy();
        }

        return new TomSelect(el, {
            ...tomSelectOptions,
            placeholder: placeholder,
            maxItems: null,
        });
    }

    function setFieldName(el, name) {
        if (!el) {
            return;
        }

        if (name) {
            el.setAttribute('name', name);
        } else {
            el.removeAttribute('name');
        }
    }

    function toggleRequired(el, required) {
        if (!el) {
            return;
        }

        if (required) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
        }
    }

    function syncShippingPaymentLinks() {
        const linkSelect = document.getElementById('payment-link-type');
        if (!linkSelect) {
            return;
        }

        const type = linkSelect.value;
        const sections = {
            sale: document.getElementById('payment-link-sale-wrap'),
            service_ticket: document.getElementById('payment-link-ssh-wrap'),
            purchase: document.getElementById('payment-link-purchase-wrap'),
            manual: document.getElementById('payment-link-manual-wrap'),
        };

        Object.entries(sections).forEach(([key, el]) => {
            if (!el) {
                return;
            }

            el.classList.toggle('hidden', key !== type);
        });

        const saleSelect = document.getElementById('payment-sale-id');
        const sshSelect = document.getElementById('payment-service-ticket-id');
        const purchaseSelect = document.getElementById('payment-purchase-id');
        const manualInput = document.getElementById('payment-for-manual');

        setFieldName(saleSelect, type === 'sale' ? 'saleIds[]' : '');
        setFieldName(sshSelect, type === 'service_ticket' ? 'serviceTicketIds[]' : '');
        toggleRequired(purchaseSelect, type === 'purchase');
        toggleRequired(manualInput, type === 'manual');

        if (type === 'sale') {
            mountTomSelect('payment-sale-id', 'Sipariş ara veya seçin...');
        }

        if (type === 'service_ticket') {
            mountTomSelect('payment-service-ticket-id', 'SSH ara veya seçin...');
        }
    }

    window.getShippingPaymentTomSelectValues = function (elementId) {
        const el = document.getElementById(elementId);
        if (!el) {
            return [];
        }

        if (el.tomselect) {
            const value = el.tomselect.getValue();
            if (Array.isArray(value)) {
                return value.filter(Boolean);
            }

            return value ? [value] : [];
        }

        if (el.multiple) {
            return Array.from(el.selectedOptions).map((option) => option.value).filter(Boolean);
        }

        return el.value ? [el.value] : [];
    };

    document.addEventListener('DOMContentLoaded', function () {
        const linkSelect = document.getElementById('payment-link-type');
        if (!linkSelect) {
            return;
        }

        linkSelect.addEventListener('change', syncShippingPaymentLinks);
        syncShippingPaymentLinks();
    });
})();
