<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        html, body {
            font-family: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #0f172a;
            background: #eef2f6;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        button, input, select, textarea, table, th, td, nav, label, a, p, span, div {
            font-family: inherit;
        }

        @include('partials.print-document-styles')

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        @media print {
            html, body {
                background: #fff !important;
                color: #0f172a !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
    @stack('print-styles')
</head>
<body class="@yield('printBodyClass')">
    <div class="no-print max-w-[210mm] mx-auto px-4 pt-5 pb-3">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">Yazdırma önizlemesi</p>
                <p class="text-xs text-slate-500 mt-0.5">PDF için <strong>PDF olarak kaydet</strong> seçin. Kenar boşlukları: <strong>Varsayılan</strong>.</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-semibold text-sm shadow-sm">Yazdır / PDF</button>
                <button type="button" onclick="window.close()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 font-semibold text-sm">Kapat</button>
            </div>
        </div>
    </div>
    <div class="px-4 pb-10 print:px-0 print:pb-0">
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
