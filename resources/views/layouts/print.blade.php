<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #000; font-size: 14px; line-height: 1.5; }
        button, input, select, textarea, table, th, td, nav, label, a { font-family: inherit; }
        .print-brand-name { font-family: 'Cormorant Garamond', Georgia, serif; color: #000 !important; }
        .print-document { border: 1px solid #ccc; border-radius: 0; font-size: 14px; line-height: 1.5; color: #000; }
        .print-document .print-doc-inner { padding: 1.25rem 1.5rem; }
        .print-signatures { border-top: 1px solid #000; padding-top: 1.25rem; margin-top: 1.5rem; }
        .print-signatures .sig-line {
            border-top: 1px solid #000;
            padding-top: 0.5rem;
            font-size: 12px;
            color: #000 !important;
            margin-top: 3rem;
        }
        .print-section { margin-bottom: 1rem; }
        .print-section-lg { margin-bottom: 1.25rem; }
        .print-brand-header { border-bottom-color: #000 !important; }
        .print-brand-header .print-doc-no { font-size: 1.75rem !important; line-height: 1.1 !important; color: #000 !important; }

        @include('partials.print-document-styles')

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        .print-document--fit .print-fit-target {
            transform-origin: top left;
        }

        @media print {
            html, body {
                width: 100%;
                height: auto;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 11pt;
                line-height: 1.35;
            }

            .no-print { display: none !important; }

            .print-document {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
                font-size: 11pt !important;
                color: #000 !important;
            }

            .print-document .print-doc-inner {
                padding: 0 !important;
            }

            .print-document .print-section {
                margin-bottom: 10px !important;
            }

            .print-document .print-section-lg {
                margin-bottom: 12px !important;
            }

            .print-document h1 { font-size: 16pt !important; line-height: 1.2 !important; }
            .print-document h2 { font-size: 13pt !important; line-height: 1.2 !important; }
            .print-document h3 { font-size: 9pt !important; margin-bottom: 4px !important; letter-spacing: 0.04em; }
            .print-document p,
            .print-document li { font-size: 9.5pt !important; line-height: 1.35 !important; }
            .print-document td,
            .print-document th { font-size: 9.5pt !important; line-height: 1.3 !important; }
            .print-document .print-doc-no { font-size: 18pt !important; line-height: 1.05 !important; }

            .print-document img,
            .print-document .print-brand-logo { max-height: 42px !important; margin-bottom: 4px !important; }

            .print-document table th,
            .print-document table td {
                padding: 5px 6px !important;
            }

            .print-table {
                table-layout: fixed;
                width: 100% !important;
            }

            .print-table thead th {
                font-size: 8pt !important;
                padding: 5px 6px !important;
            }

            .print-document--fit .print-col-no { width: 6% !important; }
            .print-document--fit .print-col-name { width: auto !important; word-break: break-word; }
            .print-document--fit .print-col-price { width: 16% !important; white-space: nowrap; }
            .print-document--fit .print-col-qty { width: 8% !important; }
            .print-document--fit .print-col-kdv { width: 8% !important; }
            .print-document--fit .print-col-total { width: 16% !important; white-space: nowrap; }

            .print-document--fit .print-brand-header {
                padding-bottom: 8px !important;
                margin-bottom: 8px !important;
                gap: 12px !important;
            }

            .print-document--fit .print-brand-name {
                font-size: 11pt !important;
            }

            .print-document--fit .print-info-banner {
                padding: 6px 8px !important;
                margin-bottom: 8px !important;
                font-size: 9pt !important;
                line-height: 1.3 !important;
            }

            .print-document--fit .print-party-grid {
                margin-bottom: 8px !important;
                gap: 8px !important;
            }

            .print-document--fit .item-description-list {
                margin-top: 2px !important;
                font-size: 8pt !important;
                line-height: 1.25 !important;
            }

            .print-document--fit .item-description-list li {
                margin: 0 !important;
            }

            .print-document--fit .print-totals-block {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-document--fit .print-items-table + .print-totals-block {
                break-before: avoid;
                page-break-before: avoid;
            }

            .print-document .print-table thead {
                display: table-header-group;
            }

            .print-document--fit .print-table tr {
                break-inside: auto;
                page-break-inside: auto;
            }

            .print-document:not(.print-document--fit) .print-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-document .print-signatures {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
            }

            .print-signatures .sig-line {
                margin-top: 2rem !important;
                font-size: 9pt !important;
            }

            .print-document--compact .print-info-banner {
                padding: 6px 8px !important;
                margin-bottom: 8px !important;
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
                var pageHeight = 252 * mmToPx;
                var pageWidth = 190 * mmToPx;
                var height = target.scrollHeight;
                var width = target.scrollWidth;
                var scale = Math.min(1, pageHeight / height, pageWidth / width);

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
