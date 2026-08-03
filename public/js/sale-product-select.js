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

    function parseRowPrice(priceEl) {
        if (!priceEl) return NaN;
        var raw = priceEl.getAttribute('data-raw');
        if (raw !== null && raw !== '') {
            var parsedRaw = parseFloat(String(raw).replace(',', '.'));
            if (!isNaN(parsedRaw)) return parsedRaw;
        }
        if (typeof window.parseLinePrice === 'function') {
            var linePrice = window.parseLinePrice(priceEl.value);
            if (!isNaN(linePrice)) return linePrice;
        }
        if (typeof window.parseMoney === 'function') {
            var money = window.parseMoney(priceEl.value);
            if (!isNaN(money)) return money;
        }
        return parseFloat(String(priceEl.value || '').replace(/\./g, '').replace(',', '.'));
    }

    function syncProductSelectLabel(row, product) {
        if (!row || !product) return;
        var sel = row.querySelector('.item-product');
        var ts = sel && sel.tomselect;
        if (!ts) return;

        var priceEl = row.querySelector('.item-price');
        var price = parseRowPrice(priceEl);
        if (isNaN(price) || price <= 0) {
            price = parseFloat(product.price) || 0;
        }

        var id = String(product.id);
        var name = product.name || product.label || '';
        if (name.indexOf(' · ') !== -1) {
            name = name.split(' · ')[0];
        }
        var customLabel = name + ' · ' + fmtPrice(price) + ' ₺';
        var updated = Object.assign({}, getSaleProduct(id) || product, {
            id: id,
            name: name,
            label: customLabel,
            price: price,
        });
        registry.set(id, updated);

        if (ts.options[id]) {
            Object.assign(ts.options[id], updated);
        } else {
            ts.addOption(updated);
        }

        if (typeof ts.updateOption === 'function') {
            ts.updateOption(id, updated);
        }
    }

    function bindPriceInput(row) {
        var priceEl = row.querySelector('.item-price');
        if (!priceEl || priceEl.dataset.priceBound === '1') return;
        priceEl.dataset.priceBound = '1';

        function onPriceEdited() {
            priceEl.dataset.priceCustom = '1';
            var productId = row.querySelector('.item-product-id') && row.querySelector('.item-product-id').value;
            var product = productId ? getSaleProduct(productId) : null;
            if (product) {
                syncProductSelectLabel(row, product);
            }
            if (typeof window.updateSaleTotals === 'function') {
                window.updateSaleTotals();
            }
        }

        priceEl.addEventListener('input', onPriceEdited);
        priceEl.addEventListener('change', onPriceEdited);
    }

    function applyProductToRow(row, product, options) {
        options = options || {};
        if (!row || !product) return;
        var idInput = row.querySelector('.item-product-id');
        var nameInput = row.querySelector('.item-product-name');
        var priceEl = row.querySelector('.item-price');
        var kdvEl = row.querySelector('.item-kdv');
        var qtyEl = row.querySelector('.item-qty');
        if (idInput) idInput.value = String(product.id);
        if (nameInput) nameInput.value = '';
        if (priceEl) {
            var isCustom = priceEl.dataset.priceCustom === '1';
            var currentPrice = parseRowPrice(priceEl);
            var shouldApplyCatalogPrice = options.forcePrice
                || (!isCustom && (isNaN(currentPrice) || currentPrice <= 0));
            if (shouldApplyCatalogPrice) {
                priceEl.value = fmtPrice(product.price);
                priceEl.setAttribute('data-raw', String(product.price));
                delete priceEl.dataset.priceCustom;
            }
            syncProductSelectLabel(row, product);
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
            dropdownClass: 'ts-dropdown sale-product-dropdown',
            preload: true,
            loadThrottle: 200,
            openOnFocus: true,
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
                if (!this.loading && this.options && Object.keys(this.options).length <= 1) {
                    this.load('');
                }
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
                if (row.dataset.restoring === '1') {
                    const product = getSaleProduct(value);
                    if (product) {
                        if (idInput) idInput.value = String(product.id);
                        if (nameInput) nameInput.value = '';
                    } else {
                        if (idInput) idInput.value = '';
                        if (nameInput) nameInput.value = value;
                    }
                    return;
                }
                var priceEl = row.querySelector('.item-price');
                if (priceEl) {
                    delete priceEl.dataset.priceCustom;
                }
                const product = getSaleProduct(value);
                if (product) {
                    applyProductToRow(row, product, { forcePrice: true });
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
        bindPriceInput(row);
        return ts;
    }

    function seedSaleProducts(products) {
        (products || []).forEach(registerSaleProduct);
    }

    window.registerSaleProduct = registerSaleProduct;
    window.getSaleProduct = getSaleProduct;
    window.initSaleProductSelect = initSaleProductSelect;
    window.seedSaleProducts = seedSaleProducts;
    window.applySaleProductToRow = applyProductToRow;
    window.syncSaleProductSelectLabel = syncProductSelectLabel;
    window.saleProductRegistry = registry;
})();
