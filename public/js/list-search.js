(function () {
    function initListSearch(options) {
        var form = document.getElementById(options.formId);
        var input = options.inputId ? document.getElementById(options.inputId) : null;
        var results = document.getElementById(options.resultsId);
        if (!form || !results) {
            return;
        }

        var debounceMs = options.debounceMs || 650;
        var timer = null;
        var controller = null;
        var loading = false;

        function buildUrl() {
            var url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
            var data = new FormData(form);
            url.search = '';
            data.forEach(function (value, key) {
                if (value !== '') {
                    url.searchParams.set(key, value);
                }
            });
            url.searchParams.delete('page');
            return url;
        }

        function setLoading(isLoading) {
            loading = isLoading;
            results.classList.toggle('opacity-50', isLoading);
            results.classList.toggle('pointer-events-none', isLoading);
        }

        function refresh(pushHistory) {
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            var url = buildUrl();

            setLoading(true);

            fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-List-Partial': '1',
                    'Accept': 'text/html',
                },
                signal: controller.signal,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('partial failed');
                    }
                    return response.text();
                })
                .then(function (html) {
                    results.outerHTML = html;
                    results = document.getElementById(options.resultsId);
                    if (pushHistory) {
                        window.history.replaceState(null, '', url.toString());
                    }
                    if (typeof options.onUpdated === 'function') {
                        options.onUpdated(results);
                    }
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    form.submit();
                })
                .finally(function () {
                    setLoading(false);
                });
        }

        function scheduleRefresh() {
            clearTimeout(timer);
            timer = setTimeout(function () {
                refresh(true);
            }, debounceMs);
        }

        if (input) {
            input.addEventListener('input', scheduleRefresh);
            if (input.value !== '') {
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }
        }

        form.addEventListener('submit', function (event) {
            if (event.submitter && event.submitter.type === 'submit') {
                return;
            }
            event.preventDefault();
            refresh(true);
        });

        results.addEventListener('click', function (event) {
            var link = event.target.closest('a[href]');
            if (!link || !results.contains(link)) {
                return;
            }
            if (link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }
            if (!link.closest('.pagination-nav, nav[role="navigation"]')) {
                return;
            }
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#') {
                return;
            }
            var url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) {
                return;
            }
            event.preventDefault();
            window.history.replaceState(null, '', url.toString());
            fetch(url.toString(), {
                headers: {
                    'X-List-Partial': '1',
                    'Accept': 'text/html',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('partial failed');
                    }
                    return response.text();
                })
                .then(function (html) {
                    results.outerHTML = html;
                    results = document.getElementById(options.resultsId);
                    if (typeof options.onUpdated === 'function') {
                        options.onUpdated(results);
                    }
                })
                .catch(function () {
                    window.location.href = url.toString();
                });
        });

        return { refresh: refresh };
    }

    window.initListSearch = initListSearch;
})();
