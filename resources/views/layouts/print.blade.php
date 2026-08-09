<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #171717;
            background: #f5f5f4;
            font-size: 14px;
            line-height: 1.5;
        }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }

        @include('partials.print-document-styles')

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        @media print {
            html, body {
                background: #fff !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
    @stack('print-styles')
</head>
<body class="@yield('printBodyClass')">
    <div class="no-print max-w-[210mm] mx-auto px-4 pt-4 pb-2">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="text-sm text-neutral-600">Yazdırma önizlemesi — PDF için <strong>PDF olarak kaydet</strong> seçin. Kenar boşlukları: <strong>Varsayılan</strong>.</p>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 font-medium text-sm">Yazdır / PDF</button>
                <button type="button" onclick="window.close()" class="px-4 py-2 bg-white border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 font-medium text-sm">Kapat</button>
            </div>
        </div>
    </div>
    <div class="px-4 pb-8 print:px-0 print:pb-0">
        @yield('content')
    </div>
    <script>
        window.addEventListener('load', function () {
            if (window.location.search.includes('auto=1')) {
                window.setTimeout(function () { window.print(); }, 300);
            }
        });
    </script>
</body>
</html>
