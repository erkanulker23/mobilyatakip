<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --print-ink: #000;
            --print-border: #000;
        }

        body { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--print-ink); font-size: 14px; line-height: 1.5; }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }
        .print-brand-name { font-family: 'Cormorant Garamond', Georgia, serif; color: var(--print-ink) !important; }
        .print-document { border: 1px solid #ccc; border-radius: 0; font-size: 14px; line-height: 1.5; color: var(--print-ink); }
        .print-document .print-doc-inner { padding: 1.25rem 1.5rem; }
        .print-document :where(p, li, dd, dt, span, div, label, strong, em, a):not(.print-table thead *) {
            color: var(--print-ink) !important;
        }
        .print-document :where(h1, h2, h3, h4) {
            color: var(--print-ink) !important;
        }
        .print-document [class*="text-neutral"],
        .print-document [class*="text-slate"],
        .print-document [class*="text-gray"] {
            color: var(--print-ink) !important;
        }
        .print-table { border-collapse: collapse; width: 100%; }
        .print-table thead { background: var(--print-ink) !important; color: #fff !important; }
        .print-table thead th {
            color: #fff !important;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            font-size: 11px !important;
            padding: 10px 12px !important;
            border: 1px solid var(--print-ink) !important;
        }
        .print-table tbody td {
            color: var(--print-ink) !important;
            font-size: 13px !important;
            padding: 10px 12px !important;
            vertical-align: top;
            border: 1px solid #333 !important;
        }
        .print-table tbody tr:nth-child(even) { background: #fff !important; }
        .print-info-banner {
            background: #fff !important;
            border-left: 3px solid var(--print-ink) !important;
            border-radius: 0 !important;
            padding: 12px 14px !important;
            font-size: 13px !important;
            color: var(--print-ink) !important;
        }
        .print-signatures { border-top: 1px solid var(--print-border); padding-top: 1.25rem; margin-top: 1.5rem; }
        .print-signatures .sig-line {
            border-top: 1px solid var(--print-ink);
            padding-top: 0.5rem;
            font-size: 12px;
            color: var(--print-ink) !important;
            margin-top: 3rem;
        }
        .print-section { margin-bottom: 1rem; }
        .print-section-lg { margin-bottom: 1.25rem; }
        .print-brand-header { border-bottom-color: var(--print-ink) !important; }
        .print-brand-header .print-doc-no { font-size: 1.75rem !important; line-height: 1.1 !important; color: var(--print-ink) !important; }
        .print-document table:not(.print-table) th,
        .print-document table:not(.print-table) td {
            color: var(--print-ink) !important;
            border-color: #333 !important;
        }

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
                color: #000 !important;
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
                color: #000 !important;
            }

            .print-table thead,
            .print-table thead th {
                background: #000 !important;
                color: #fff !important;
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
<body class="font-sans bg-white text-black p-4 md:p-6 @yield('printBodyClass')">
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
