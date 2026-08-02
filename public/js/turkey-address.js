(function () {
    var citiesCache = null;
    var citiesPromise = null;

    function citiesUrl() {
        return document.querySelector('meta[name="turkey-cities-url"]')?.content || '/api/turkey/cities';
    }

    function districtsUrl(cityId) {
        var base = document.querySelector('meta[name="turkey-districts-url"]')?.content || '/api/turkey/districts';
        return base + '?cityId=' + encodeURIComponent(cityId);
    }

    function loadCities() {
        if (citiesCache) {
            return Promise.resolve(citiesCache);
        }
        if (citiesPromise) {
            return citiesPromise;
        }
        citiesPromise = fetch(citiesUrl(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('İl listesi alınamadı (HTTP ' + response.status + ')');
                }
                return response.json();
            })
            .then(function (data) {
                citiesCache = Array.isArray(data) ? data : [];
                return citiesCache;
            })
            .catch(function (err) {
                citiesPromise = null;
                console.error('[TurkeyAddress]', err.message || 'İl listesi yüklenemedi');
                return [];
            });
        return citiesPromise;
    }

    function loadDistricts(cityId) {
        if (!cityId) {
            return Promise.resolve([]);
        }
        return fetch(districtsUrl(cityId), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('İlçe listesi alınamadı (HTTP ' + response.status + ')');
                }
                return response.json();
            })
            .then(function (data) {
                return Array.isArray(data) ? data : [];
            })
            .catch(function (err) {
                console.error('[TurkeyAddress]', err.message || 'İlçe listesi yüklenemedi');
                return [];
            });
    }

    function fillSelect(select, items, selectedId, placeholder) {
        if (!select) {
            return;
        }
        select.innerHTML = '';
        var empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        select.appendChild(empty);
        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            if (selectedId && String(selectedId) === String(item.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function initContainer(container) {
        if (!container || container.dataset.turkeyAddressReady === '1') {
            return;
        }

        var citySelect = container.querySelector('.turkey-city-select');
        var districtSelect = container.querySelector('.turkey-district-select');
        if (!citySelect || !districtSelect) {
            return;
        }

        container.dataset.turkeyAddressReady = '1';

        var initialCityId = container.dataset.cityId || '';
        var initialDistrictId = container.dataset.districtId || '';

        loadCities().then(function (cities) {
            fillSelect(citySelect, cities, initialCityId, 'İl seçiniz');

            if (initialCityId) {
                return loadDistricts(initialCityId).then(function (districts) {
                    fillSelect(districtSelect, districts, initialDistrictId, 'İlçe seçiniz');
                });
            }

            fillSelect(districtSelect, [], '', 'Önce il seçiniz');
        });

        citySelect.addEventListener('change', function () {
            var cityId = citySelect.value;
            districtSelect.disabled = true;
            fillSelect(districtSelect, [], '', 'Yükleniyor...');

            loadDistricts(cityId).then(function (districts) {
                fillSelect(
                    districtSelect,
                    districts,
                    '',
                    cityId ? 'İlçe seçiniz' : 'Önce il seçiniz'
                );
                districtSelect.disabled = false;
            });
        });
    }

    function initAll(root) {
        (root || document).querySelectorAll('.turkey-address-fields').forEach(initContainer);
    }

    window.TurkeyAddress = {
        loadCities: loadCities,
        loadDistricts: loadDistricts,
        fillSelect: fillSelect,
        init: initAll,
        initAlpine: function (root, cityModel, districtModel, cityKey, districtKey) {
            cityKey = cityKey || 'cityId';
            districtKey = districtKey || 'districtId';

            return {
                cities: [],
                districts: [],
                cityId: root[cityKey] || '',
                districtId: root[districtKey] || '',
                async initPicker() {
                    this.cities = await loadCities();
                    this.cityId = root[cityKey] || '';
                    this.districtId = root[districtKey] || '';
                    if (this.cityId) {
                        this.districts = await loadDistricts(this.cityId);
                    }
                },
                async onCityChange() {
                    root[cityKey] = this.cityId;
                    root[districtKey] = '';
                    this.districtId = '';
                    this.districts = this.cityId ? await loadDistricts(this.cityId) : [];
                },
                onDistrictChange() {
                    root[districtKey] = this.districtId;
                },
            };
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    if (document.readyState !== 'loading') {
        initAll(document);
    }

    document.addEventListener('turkey-address:refresh', function (event) {
        initAll(event.detail?.root || document);
    });
})();
