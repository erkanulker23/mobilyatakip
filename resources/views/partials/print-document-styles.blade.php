:root {
    --print-ink: #000;
    --print-border: #000;
    --print-head-bg: #ececec;
}

.print-document,
.print-document * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.print-document :where(p, li, dd, dt, span, div, label, strong, em, a, td):not(.print-table thead *) {
    color: var(--print-ink) !important;
}

.print-document :where(h1, h2, h3, h4) {
    color: var(--print-ink) !important;
}

.print-document .print-table tbody [class*="text-neutral"],
.print-document .print-table tbody [class*="text-slate"],
.print-document .print-table tbody [class*="text-gray"],
.print-document .print-table tbody [class*="text-emerald"],
.print-document .print-table tbody [class*="text-red"],
.print-document .print-table tbody [class*="text-amber"],
.print-document [class*="text-neutral"]:not(.print-table thead *),
.print-document [class*="text-slate"]:not(.print-table thead *),
.print-document [class*="text-gray"]:not(.print-table thead *),
.print-document [class*="text-emerald"],
.print-document [class*="text-red-"],
.print-document [class*="text-amber"],
.print-document .amount-negative {
    color: var(--print-ink) !important;
}

.print-table { border-collapse: collapse; width: 100%; }
.print-table thead {
    background: var(--print-head-bg) !important;
    color: var(--print-ink) !important;
}
.print-table thead th {
    background: var(--print-head-bg) !important;
    color: var(--print-ink) !important;
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
    background: #fff !important;
}
.print-table tbody tr:nth-child(even) td { background: #fff !important; }

.print-document [class*="bg-neutral-50"],
.print-document [class*="bg-slate-50"],
.print-document [class*="bg-red-50"],
.print-document [class*="bg-emerald-50"],
.print-document [class*="bg-amber-50"] {
    background: #fff !important;
    color: var(--print-ink) !important;
}

.print-document span[class*="rounded-full"],
.print-document [class*="badge"] {
    background: #fff !important;
    border: 1px solid var(--print-ink) !important;
    color: var(--print-ink) !important;
    box-shadow: none !important;
}

.print-info-banner {
    background: #fff !important;
    border-left: 3px solid var(--print-ink) !important;
    border-radius: 0 !important;
    padding: 12px 14px !important;
    font-size: 13px !important;
    color: var(--print-ink) !important;
}

.print-document table:not(.print-table) th,
.print-document table:not(.print-table) td {
    color: var(--print-ink) !important;
    border-color: #333 !important;
}

@media print {
    .print-document .print-table thead,
    .print-document .print-table thead th {
        background: var(--print-head-bg) !important;
        color: var(--print-ink) !important;
    }

    .print-document span[class*="rounded-full"],
    .print-document [class*="badge"] {
        background: #fff !important;
        border: 1px solid #000 !important;
        color: #000 !important;
    }
}
