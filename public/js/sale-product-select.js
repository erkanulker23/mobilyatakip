(function () {
    'use strict';

    const registry = new Map();

    function fmtPrice(n) {
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0);
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase('tr-TR')
            .replace(/ı/g, 'i')
            .replace(/ş/g, 's')
            .replace(/ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/ö/g, 'o')
            .replace(/ç/g, 'c');
    }

    function registerSaleProduct(product) {
        if (!product || product.id == null) return product;
        const id = String(product.id);
        const payload = {
            id,
            name: product.name || product.label || '',
            label: product.label || ((product.name || '') + ' · ' + fmtPrice(product.price) + ' ₺'),
            sku: product.sku || '',
            supplier: product.supplier || '',
            price: parseFloat(product.price) || 0,
            kdv: parseFloat(product.kdv) ?? 10,
            image: product.image || null,
            searchText: product.searchText || normalizeSearch([product.name, product.sku, product.supplier].filter(Boolean).join(' ')),
        };
        registry.set(id, payload);
        return payload;
    }

    function getSaleProduct(id) {
        if (id == null || id === '') return null;
        return registry.get(String(id)) || null;
    }

    function productPlaceholder() {
        return '<div class="w-10 h-10 rounded-lg bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center shrink-0 text-neutral-400">'
            + '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'
            + '</div>';
    }

    function renderProductOption(data, escape) {
        const imgHtml = data.image
            ? '<img src="' + escape(data.image) + '" alt="" class="w-10 h-10 object-cover rounded-lg shrink-0" onerror="this.outerHTML=\'' + productPlaceholder().replace(/'/g, "\\'") + '\'">'
            : productPlaceholder();
        const meta = [data.sku ? 'Stok: ' + data.sku : '', data.supplier].filter(Boolean).join(' · ');
        return '<div class="sale-product-option flex items-center gap-3 py-0.5">'
            + imgHtml
            + '<div class="min-w-0 flex-1">'
            + '<div class="font-medium text-sm leading-snug truncate">' + escape(data.name) + '</div>'
            + (meta ? '<div class="text-xs text-neutral-500 truncate">' + escape(meta) + '</div>' : '')
            + '</div>'
            + '<div class="text-sm font-semibold shrink-0 tabular-nums">' + escape(fmtPrice(data.price)) + ' ₺</div>'
            + '</div>';
    }

    function fetchProducts(query, selectedIds) {
        const url = new URL(window.SALE_PRODUCT_SEARCH_URL || '/api/products/search', window.location.origin);
        url.searchParams.set('q', query || '');
        url.searchParams.set('limit', '40');
        (selectedIds || []).forEach(function (id) {
            url.searchParams.append('ids[]', id);
        });
        return fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (res) {
            if (!res.ok) throw new Error('search failed');
            return res.json();
        });
    }

    function applyProductToRow(row, product) {
        if (!row || !product) return;
        const idInput = row.querySelector('.item-product-id');
        const nameInput = row.querySelector('.item-product-name');
        const priceEl = row.querySelector('.item-price');
        const kdvEl = row.querySelector('.item-kdv');
        const qtyEl = row.querySelector('.item-qty');
        if (idInput) idInput.value = String(product.id);
        if (nameInput) nameInput.value = '';
        if (priceEl) {
            priceEl.value = fmtPrice(product.price);
            priceEl.setAttribute('data-raw', String(product.price));
        }
        if (kdvEl) kdvEl.value = product.kdv ?? 10;
        if (qtyEl && (!qtyEl.value || parseInt(qtyEl.value, 10) < 1)) qtyEl.value = '1';
    }

    function initSaleProductSelect(sel, rowIdx) {
        if (!sel || typeof TomSelect === 'undefined' || sel.tomselect) return sel?.tomselect;
        window.salesProductSelects = window.salesProductSelects || [];
        const row = sel.closest('.item-row');
        const idInput = row.querySelector('.item-product-id');
        const nameInput = row.querySelector('.item-product-name');

        const ts = new TomSelect(sel, {
            create: true,
            createOnBlur: true,
            maxOptions: 40,
            maxItems: 1,
            valueField: 'id',
            labelField: 'label',
            searchField: ['name', 'sku', 'supplier', 'searchText', 'label'],
            sortField: [{ field: 'name', direction: 'asc' }],
            placeholder: 'Ürün adı, stok kodu veya tedarikçi ara...',
            dropdownParent: 'body',
            dropdownClass: 'sale-product-dropdown',
            preload: true,
            loadThrottle: 200,
            shouldLoad: function () {
                return true;
            },
            load: function (query, callback) {
                const selectedId = this.getValue();
                const ids = selectedId && getSaleProduct(selectedId) ? [selectedId] : [];
                fetchProducts(query, ids)
                    .then(function (json) {
                        (json.products || []).forEach(registerSaleProduct);
                        callback(json.products || []);
                    })
                    .catch(function () {
                        callback();
                    });
            },
            score: function (search) {
                const normalized = normalizeSearch(search);
                if (!normalized) {
                    return function () { return 1; };
                }
                const terms = normalized.split(/\s+/).filter(Boolean);
                return function (item) {
                    const haystack = normalizeSearch(item.searchText || item.name || item.label || '');
                    if (!terms.every(function (term) { return haystack.indexOf(term) !== -1; })) {
                        return 0;
                    }
                    const name = normalizeSearch(item.name || '');
                    if (name.indexOf(normalized) === 0) return 100;
                    if (name.indexOf(terms[0]) !== -1) return 50;
                    return 10;
                };
            },
            onDropdownOpen: function () {
                const rect = this.control.getBoundingClientRect();
                const viewportH = window.innerHeight || document.documentElement.clientHeight;
                if (rect.bottom > viewportH - 260) {
                    this.dropdown.classList.add('dropup');
                }
                if (!this.loading && this.options && Object.keys(this.options).length <= 1) {
                    this.load('');
                }
            },
            onDropdownClose: function () {
                this.dropdown.classList.remove('dropup');
            },
            render: {
                option_create: function (data, escape) {
                    return '<div class="create px-1 py-1 text-sm">+ "' + escape(data.input) + '" olarak manuel ekle</div>';
                },
                no_results: function () {
                    return '<div class="no-results px-3 py-2 text-sm text-neutral-500">Sonuç yok — yazmaya devam edin veya manuel ekleyin</div>';
                },
                loading: function () {
                    return '<div class="px-3 py-2 text-sm text-neutral-500">Ürünler aranıyor...</div>';
                },
                item: function (data, escape) {
                    const product = getSaleProduct(data.id) || data;
                    const imgHtml = product.image
                        ? '<img src="' + escape(product.image) + '" alt="" class="w-7 h-7 object-cover rounded shrink-0" onerror="this.style.display=\'none\'">'
                        : '';
                    return '<div class="flex items-center gap-2 min-w-0">'
                        + (imgHtml ? '<span class="shrink-0">' + imgHtml + '</span>' : '')
                        + '<span class="truncate">' + escape(product.label || product.name) + '</span>'
                        + '</div>';
                },
                option: function (data, escape) {
                    const product = getSaleProduct(data.id) || data;
                    return renderProductOption(product, escape);
                },
            },
            onItemAdd: function (value) {
                const product = getSaleProduct(value);
                if (product) {
                    applyProductToRow(row, product);
                    if (idInput) idInput.value = String(product.id);
                    if (nameInput) nameInput.value = '';
                } else {
                    if (idInput) idInput.value = '';
                    if (nameInput) nameInput.value = value;
                }
                if (typeof window.updateSaleTotals === 'function') {
                    window.updateSaleTotals();
                }
            },
            onClear: function () {
                if (idInput) idInput.value = '';
                if (nameInput) nameInput.value = '';
            },
        });

        ts.on('change', function (value) {
            if (value && value !== '') {
                const product = getSaleProduct(value);
                if (product) {
                    if (idInput) idInput.value = String(product.id);
                    if (nameInput) nameInput.value = '';
                } else {
                    if (idInput) idInput.value = '';
                    if (nameInput) nameInput.value = value;
                }
            } else {
                if (idInput) idInput.value = '';
                if (nameInput) nameInput.value = '';
            }
        });

        window.salesProductSelects[rowIdx] = ts;
        return ts;
    }

    function seedSaleProducts(products) {
        (products || []).forEach(registerSaleProduct);
    }

    window.registerSaleProduct = registerSaleProduct;
    window.getSaleProduct = getSaleProduct;
    window.initSaleProductSelect = initSaleProductSelect;
    window.seedSaleProducts = seedSaleProducts;
    window.saleProductRegistry = registry;
})();
