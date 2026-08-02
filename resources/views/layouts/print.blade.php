<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #171717; }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }
        .print-brand-name { font-family: 'Cormorant Garamond', Georgia, serif; }
        .print-document { border: 1px solid #e5e5e5; border-radius: 0; }
        .print-table thead { background: #171717 !important; color: #fff !important; }
        .print-table thead th { color: #fff !important; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; font-size: 9px !important; }
        .print-table tbody tr:nth-child(even) { background: #fafafa; }
        .print-info-banner { background: #fafafa !important; border-left: 3px solid #171717 !important; border-radius: 0 !important; }
        .print-signatures { border-top: 1px solid #d4d4d4; padding-top: 1rem; }
        .print-signatures .sig-line { border-top: 1px solid #171717; padding-top: 0.35rem; font-size: 10px; color: #525252; }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            html, body {
                width: 100%;
                height: auto;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print { display: none !important; }

            .print-document {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }

            .print-document .print-doc-inner {
                padding: 0 !important;
            }

            .print-document .print-section {
                margin-bottom: 0.5rem !important;
            }

            .print-document .print-section-lg {
                margin-bottom: 0.75rem !important;
            }

            .print-document h1 { font-size: 14px !important; line-height: 1.2 !important; }
            .print-document h2 { font-size: 12px !important; line-height: 1.2 !important; }
            .print-document h3 { font-size: 9px !important; margin-bottom: 2px !important; }
            .print-document p,
            .print-document td,
            .print-document th,
            .print-document li { font-size: 10px !important; line-height: 1.35 !important; }
            .print-document .print-doc-no { font-size: 16px !important; line-height: 1.1 !important; }

            .print-document img { max-height: 36px !important; margin-bottom: 4px !important; }

            .print-document table th,
            .print-document table td {
                padding: 3px 5px !important;
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
                margin-top: 0.5rem !important;
                padding-top: 0.5rem !important;
            }

            .print-document--compact .print-info-banner {
                padding: 4px 8px !important;
                margin-bottom: 0.5rem !important;
            }

            .print-fit-wrapper {
                overflow: hidden;
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
    <div class="print-fit-wrapper" id="print-fit-wrapper">
        @yield('content')
    </div>
    <script>
        (function () {
            var A4_CONTENT_HEIGHT = 1050;

            function resetPrintFit() {
                var wrapper = document.getElementById('print-fit-wrapper');
                var doc = wrapper ? wrapper.querySelector('.print-document--fit') : null;
                if (!doc) return;
                doc.style.transform = '';
                doc.style.transformOrigin = '';
                doc.style.width = '';
                if (wrapper) wrapper.style.height = '';
            }

            function fitPrintDocument() {
                resetPrintFit();
                var wrapper = document.getElementById('print-fit-wrapper');
                var doc = wrapper ? wrapper.querySelector('.print-document--fit') : null;
                if (!doc) return;

                var height = doc.getBoundingClientRect().height;
                if (height <= A4_CONTENT_HEIGHT) return;

                var scale = A4_CONTENT_HEIGHT / height;
                scale = Math.max(scale, 0.55);
                doc.style.transformOrigin = 'top center';
                doc.style.transform = 'scale(' + scale + ')';
                doc.style.width = (100 / scale) + '%';
                if (wrapper) wrapper.style.height = (height * scale) + 'px';
            }

            window.addEventListener('beforeprint', fitPrintDocument);
            window.addEventListener('afterprint', resetPrintFit);

            window.onload = function () {
                if (window.location.search.includes('auto=1')) window.print();
            };
        })();
    </script>
</body>
</html>
