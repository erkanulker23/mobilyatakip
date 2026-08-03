<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #171717;
            background: #f5f5f4;
            font-size: 14px;
            line-height: 1.5;
        }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }

        @include('partials.print-document-styles')

        @page {
            size: A4 portrait;
            margin: 12mm;
        }
    </style>
    @stack('print-styles')
</head>
<body class="font-sans @yield('printBodyClass')">
    <div class="no-print max-w-[210mm] mx-auto px-4 pt-4 pb-2">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="text-sm text-neutral-600">Yazdırma önizlemesi — en iyi sonuç için kenar boşluklarını <strong>Varsayılan</strong> bırakın.</p>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 font-medium text-sm">Yazdır</button>
                <button onclick="window.close()" class="px-4 py-2 bg-white border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 font-medium text-sm">Kapat</button>
            </div>
        </div>
    </div>
    <div class="px-4 pb-8 print:px-0 print:pb-0">
        @yield('content')
    </div>
    <script>
        (function () {
            var fitTarget = null;
            var fitHost = null;

            function resetPrintFit() {
                if (!fitTarget) return;
                fitTarget.style.transform = '';
                fitTarget.style.width = '';
                if (fitHost) {
                    fitHost.style.height = '';
                    fitHost.style.overflow = '';
                }
                fitTarget = null;
                fitHost = null;
            }

            function fitPrintDocumentToPage() {
                resetPrintFit();
                var host = document.querySelector('.print-document--fit');
                if (!host) return;
                var target = host.querySelector('.print-fit-target') || host;
                fitHost = host;
                fitTarget = target;

                var mmToPx = 96 / 25.4;
                var pageHeight = 258 * mmToPx;
                var pageWidth = 186 * mmToPx;
                var height = target.scrollHeight;
                var width = target.scrollWidth;

                // Çok sayfalı belgelerde ölçekleme yapma; doğal sayfalama kullan
                if (height > pageHeight * 1.08) {
                    return;
                }

                var scale = Math.min(1, pageHeight / height, pageWidth / width);
                scale = Math.max(0.88, scale);

                if (scale < 0.995) {
                    target.style.transformOrigin = 'top left';
                    target.style.transform = 'scale(' + scale + ')';
                    target.style.width = (100 / scale) + '%';
                    host.style.height = Math.ceil(height * scale) + 'px';
                    host.style.overflow = 'hidden';
                }
            }

            window.addEventListener('beforeprint', fitPrintDocumentToPage);
            window.addEventListener('afterprint', resetPrintFit);
        })();

        window.onload = function () {
            if (window.location.search.includes('auto=1')) window.print();
        };
    </script>
</body>
</html>
