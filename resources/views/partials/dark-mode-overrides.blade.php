{{-- Ekranda koyu mod: eksik dark: sınıfları ve fatura önizlemesi --}}
.dark .text-black { color: #f5f5f5; }
.dark .text-neutral-800 { color: #d4d4d4; }
.dark .text-neutral-700 { color: #d4d4d4; }
.dark .bg-neutral-50 { background-color: #262626; }
.dark .text-amber-950 { color: #fde68a; }
.dark .text-amber-900 { color: #fcd34d; }
.dark .hover\:text-neutral-900:hover { color: #f5f5f5; }
.dark .border-black { border-color: #525252; }
.dark .bg-blue-100 { background-color: rgba(30, 58, 138, 0.35); }
.dark .text-blue-700 { color: #93c5fd; }
.dark .bg-amber-200 { background-color: rgba(146, 64, 14, 0.45); }
.dark .hover\:bg-neutral-100:hover { background-color: #404040; }
.dark .hover\:bg-amber-200:hover { background-color: rgba(146, 64, 14, 0.55); }
.dark .hover\:bg-emerald-200:hover { background-color: rgba(6, 78, 59, 0.45); }
.dark header kbd { background: #262626 !important; border-color: #404040 !important; color: #a3a3a3 !important; }

@media screen {
    /* Fatura / sevkiyat önizlemesi: koyu modda beyaz kağıt */
    .dark .print-document {
        background: #ffffff !important;
        color: #171717 !important;
        border-color: #e5e5e5 !important;
    }
    .dark .print-document .print-doc-inner {
        color: #171717;
    }
    .dark .print-document .text-black,
    .dark .print-document [class*="text-neutral"],
    .dark .print-document [class*="text-slate"],
    .dark .print-document h1,
    .dark .print-document h2,
    .dark .print-document h3,
    .dark .print-document p,
    .dark .print-document span,
    .dark .print-document td,
    .dark .print-document th,
    .dark .print-document dd,
    .dark .print-document dt,
    .dark .print-document strong {
        color: #171717 !important;
    }
    .dark .print-document .page-desc {
        color: #525252 !important;
    }
    .dark .print-document .print-brand-header {
        border-color: #a3a3a3 !important;
    }
    .dark .print-document .print-card,
    .dark .print-document .print-doc-meta,
    .dark .print-document .print-totals-panel,
    .dark .print-document .print-highlight-box {
        background: #fafafa !important;
        border-color: #e5e5e5 !important;
    }
    .dark .print-document .print-table thead,
    .dark .print-document .print-table thead th {
        background: #171717 !important;
        color: #ffffff !important;
        border: none !important;
    }
    .dark .print-document .print-table tbody td {
        background: #fff !important;
        color: #171717 !important;
        border-color: #e5e5e5 !important;
    }
    .dark .print-document .print-table tbody tr:hover td {
        background: #f8fafc !important;
    }
    .dark .print-document .print-info-banner {
        background: #fafafa !important;
        color: #171717 !important;
        border-left-color: #000 !important;
    }
    .dark .print-document .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
    .dark .print-document .divide-neutral-100 > :not([hidden]) ~ :not([hidden]) {
        border-color: #e5e5e5 !important;
    }
    .dark .print-document .border-neutral-200,
    .dark .print-document .border-slate-200 {
        border-color: #e5e5e5 !important;
    }
    .dark .print-document .bg-white,
    .dark .print-document .bg-neutral-50,
    .dark .print-document .bg-slate-50 {
        background-color: #ffffff !important;
    }
}
