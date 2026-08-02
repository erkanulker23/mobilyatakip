<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #171717; font-size: 14px; line-height: 1.5; }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }
        .print-brand-name { font-family: 'Cormorant Garamond', Georgia, serif; }
        .print-document { border: 1px solid #e5e5e5; border-radius: 0; font-size: 14px; line-height: 1.5; }
        .print-document .print-doc-inner { padding: 1.25rem 1.5rem; }
        .print-table { border-collapse: collapse; width: 100%; }
        .print-table thead { background: #171717 !important; color: #fff !important; }
        .print-table thead th {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            font-size: 11px !important;
            padding: 10px 12px !important;
        }
        .print-table tbody td {
            font-size: 13px !important;
            padding: 10px 12px !important;
            vertical-align: top;
        }
        .print-table tbody tr:nth-child(even) { background: #fafafa; }
        .print-info-banner { background: #fafafa !important; border-left: 3px solid #171717 !important; border-radius: 0 !important; padding: 12px 14px !important; font-size: 13px !important; }
        .print-signatures { border-top: 1px solid #d4d4d4; padding-top: 1.25rem; margin-top: 1.5rem; }
        .print-signatures .sig-line { border-top: 1px solid #171717; padding-top: 0.5rem; font-size: 12px; color: #525252; margin-top: 3rem; }
        .print-section { margin-bottom: 1rem; }
        .print-section-lg { margin-bottom: 1.25rem; }
        .print-brand-header .print-doc-no { font-size: 1.75rem !important; line-height: 1.1 !important; }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            html, body {
                width: 100%;
                height: auto;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                font-size: 12pt;
                line-height: 1.45;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print { display: none !important; }

            .print-document {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
                font-size: 12pt !important;
            }

            .print-document .print-doc-inner {
                padding: 0 !important;
            }

            .print-document .print-section {
                margin-bottom: 14px !important;
            }

            .print-document .print-section-lg {
                margin-bottom: 18px !important;
            }

            .print-document h1 { font-size: 18pt !important; line-height: 1.25 !important; }
            .print-document h2 { font-size: 14pt !important; line-height: 1.25 !important; }
            .print-document h3 { font-size: 11pt !important; margin-bottom: 6px !important; letter-spacing: 0.04em; }
            .print-document p,
            .print-document li { font-size: 11pt !important; line-height: 1.45 !important; }
            .print-document td,
            .print-document th { font-size: 11pt !important; line-height: 1.4 !important; }
            .print-document .print-doc-no { font-size: 22pt !important; line-height: 1.1 !important; }

            .print-document img { max-height: 52px !important; margin-bottom: 8px !important; }

            .print-document table th,
            .print-document table td {
                padding: 8px 10px !important;
            }

            .print-table thead th {
                font-size: 9pt !important;
                padding: 8px 10px !important;
            }

            .print-document .print-table thead {
                display: table-header-group;
            }

            .print-document .print-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-document .print-signatures {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-top: 1rem !important;
                padding-top: 1rem !important;
            }

            .print-signatures .sig-line {
                margin-top: 2.5rem !important;
                font-size: 10pt !important;
            }

            .print-document--compact .print-info-banner {
                padding: 10px 12px !important;
                margin-bottom: 12px !important;
            }
        }
    </style>
    @stack('print-styles')
</head>
<body class="font-sans antialiased bg-white text-slate-900 p-4 md:p-6 @yield('printBodyClass')">
    <div class="no-print mb-4 flex gap-2 print:hidden">
        <button onclick="window.print()" class="px-4 py-2 bg-neutral-900 text-white rounded-xl hover:bg-neutral-800 font-medium">Yazdır</button>
        <button onclick="window.close()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Kapat</button>
    </div>
    @yield('content')
    <script>
        window.onload = function () {
            if (window.location.search.includes('auto=1')) window.print();
        };
    </script>
</body>
</html>
