:root {
    --print-ink: #171717;
    --print-muted: #525252;
    --print-border: #e5e5e5;
    --print-border-strong: #a3a3a3;
    --print-surface: #fafafa;
    --print-head-bg: #171717;
    --print-head-text: #ffffff;
    --print-accent: #171717;
}

.print-document,
.print-document * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Tipografi ── */
.print-document {
    color: var(--print-ink);
    font-size: 13px;
    line-height: 1.5;
}

.print-document :where(p, li, dd, dt, span, div, label, strong, em, a, td):not(.print-table thead *) {
    color: inherit;
}

.print-document :where(h1, h2, h3, h4) {
    color: var(--print-ink) !important;
}

.print-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--print-muted) !important;
    margin-bottom: 6px;
}

.print-section-title {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--print-muted) !important;
    margin-bottom: 8px;
}

.print-muted { color: var(--print-muted) !important; }

.print-document [class*="text-neutral"],
.print-document [class*="text-slate"],
.print-document [class*="text-gray"],
.print-document [class*="text-emerald"],
.print-document [class*="text-red"],
.print-document [class*="text-amber"],
.print-document [class*="text-green"],
.print-document .amount-negative {
    color: var(--print-ink) !important;
}

.print-document .print-table tbody [class*="text-neutral"],
.print-document .print-table tbody [class*="text-slate"],
.print-document .print-table tbody [class*="text-gray"],
.print-document .print-table tbody [class*="text-emerald"],
.print-document .print-table tbody [class*="text-red"],
.print-document .print-table tbody [class*="text-amber"],
.print-document .print-table tbody [class*="text-green"] {
    color: var(--print-ink) !important;
}

/* ── Başlık ── */
.print-brand-header {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--print-border-strong);
}

.print-brand-logo {
    height: 48px;
    max-height: 48px;
    width: auto;
    max-width: 160px;
    margin-bottom: 8px;
    object-fit: contain;
    object-position: left;
}

.print-brand-name {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 17px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--print-ink) !important;
    line-height: 1.2;
}

.print-brand-meta {
    font-size: 11px;
    line-height: 1.45;
    color: var(--print-muted) !important;
    margin-top: 4px;
}

.print-brand-contacts {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    margin-top: 6px;
    font-size: 11px;
    color: var(--print-muted) !important;
}

.print-doc-meta {
    flex-shrink: 0;
    max-width: 44%;
    text-align: right;
    background: var(--print-surface);
    border: 1px solid var(--print-border);
    padding: 12px 16px;
}

.print-doc-type {
    display: block;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--print-muted) !important;
}

.print-doc-no {
    display: block;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.1;
    color: var(--print-ink) !important;
    margin-top: 4px;
}

.print-doc-sub {
    display: block;
    font-size: 11px;
    color: var(--print-muted) !important;
    margin-top: 6px;
    line-height: 1.4;
}

/* ── Bilgi kartları ── */
.print-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.print-card {
    background: var(--print-surface);
    border: 1px solid var(--print-border);
    padding: 12px 14px;
}

.print-card--meta p {
    font-size: 12px;
    line-height: 1.45;
    color: var(--print-muted) !important;
    margin-top: 4px;
}

.print-card--meta p:first-child {
    margin-top: 0;
}

.print-card--meta strong,
.print-card--meta .font-semibold,
.print-card--meta .font-medium {
    color: var(--print-ink) !important;
}

.print-card p,
.print-card dd,
.print-card dt {
    font-size: 12px;
    line-height: 1.45;
}

.print-card .print-party-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--print-ink) !important;
}

.print-info-banner {
    background: var(--print-surface) !important;
    border: 1px solid var(--print-border) !important;
    border-left: 3px solid var(--print-accent) !important;
    border-radius: 0 !important;
    padding: 10px 14px !important;
    font-size: 12px !important;
    line-height: 1.45 !important;
    color: var(--print-ink) !important;
    margin-bottom: 14px;
}

.print-highlight-box {
    border: 1px solid var(--print-border-strong);
    background: var(--print-surface);
    padding: 16px 18px;
    margin-bottom: 16px;
}

.print-highlight-amount {
    font-size: 24px;
    font-weight: 700;
    color: var(--print-ink) !important;
    line-height: 1.2;
    margin-top: 4px;
}

/* ── Tablo ── */
.print-table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
}

.print-table thead {
    background: var(--print-head-bg) !important;
    color: var(--print-head-text) !important;
}

.print-table thead th {
    background: var(--print-head-bg) !important;
    color: var(--print-head-text) !important;
    font-weight: 600;
    font-size: 9px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 9px 10px !important;
    border: none !important;
    vertical-align: middle;
}

.print-table tbody td {
    color: var(--print-ink) !important;
    font-size: 12px;
    padding: 8px 10px !important;
    vertical-align: top;
    border: none !important;
    border-bottom: 1px solid var(--print-border) !important;
    background: #fff !important;
}

.print-table tbody tr:last-child td {
    border-bottom: 1px solid var(--print-border-strong) !important;
}

.print-table tbody tr:nth-child(even) td {
    background: #fff !important;
}

.print-table tfoot td {
    padding: 8px 10px !important;
    font-size: 12px;
    font-weight: 600;
    border-top: 2px solid var(--print-border-strong) !important;
    background: var(--print-surface) !important;
    color: var(--print-ink) !important;
}

.print-col-no { width: 5%; }
.print-col-name { width: auto; word-break: break-word; }
.print-col-price { width: 15%; white-space: nowrap; }
.print-col-qty { width: 8%; }
.print-col-kdv { width: 8%; }
.print-col-total { width: 15%; white-space: nowrap; }

.item-description-list {
    margin-top: 3px;
    font-size: 10px;
    line-height: 1.35;
    color: var(--print-muted) !important;
}

.item-description-list li {
    margin: 0;
}

/* ── Toplamlar ── */
.print-totals-panel {
    width: min(100%, 300px);
    margin-left: auto;
    margin-top: 14px;
    background: var(--print-surface);
    border: 1px solid var(--print-border);
    padding: 12px 14px;
}

.print-totals-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 16px;
    font-size: 12px;
    padding: 3px 0;
}

.print-totals-row span:first-child {
    color: var(--print-muted) !important;
}

.print-totals-row span:last-child {
    font-weight: 500;
    text-align: right;
    min-width: 100px;
}

.print-totals-grand {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 16px;
    margin-top: 8px;
    padding-top: 10px;
    border-top: 2px solid var(--print-ink);
    font-size: 14px;
    font-weight: 700;
}

.print-totals-grand span:last-child {
    text-align: right;
    min-width: 100px;
}

.print-totals-row--after-grand {
    margin-top: 8px;
}

.print-totals-row--status {
    margin-top: 6px;
}

/* ── Notlar & imza ── */
.print-notes-block {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--print-border);
}

.print-notes-block p {
    font-size: 12px;
    line-height: 1.45;
    color: var(--print-muted) !important;
}

.print-footer-note {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--print-border);
    font-size: 10px;
    color: var(--print-muted) !important;
}

.print-signatures {
    border-top: 1px solid var(--print-border-strong);
    padding-top: 16px;
    margin-top: 20px;
}

.print-signatures .sig-line {
    border-top: 1px solid var(--print-ink);
    padding-top: 6px;
    font-size: 11px;
    color: var(--print-muted) !important;
    margin-top: 48px;
}

/* ── Rozetler ── */
.print-document [class*="bg-neutral-50"],
.print-document [class*="bg-slate-50"],
.print-document [class*="bg-red-50"],
.print-document [class*="bg-emerald-50"],
.print-document [class*="bg-amber-50"] {
    background: var(--print-surface) !important;
    color: var(--print-ink) !important;
}

.print-document span[class*="rounded-full"],
.print-document [class*="badge"] {
    background: #fff !important;
    border: 1px solid var(--print-border-strong) !important;
    color: var(--print-ink) !important;
    box-shadow: none !important;
    font-size: 10px !important;
    padding: 2px 8px !important;
}

.print-document table:not(.print-table) th,
.print-document table:not(.print-table) td {
    color: var(--print-ink) !important;
    border-color: var(--print-border) !important;
}

/* ── Ekran önizleme ── */
.print-document {
    border: 1px solid var(--print-border);
    border-radius: 0;
    max-width: 210mm;
    margin: 0 auto;
}

.print-document .print-doc-inner {
    padding: 20px 24px;
}

.print-section { margin-bottom: 14px; }
.print-section-lg { margin-bottom: 16px; }

.print-fit-target {
    transform-origin: top left;
}

@media print {
    .print-document {
        border: none !important;
        max-width: none !important;
        margin: 0 !important;
        font-size: 10.5pt !important;
        line-height: 1.4 !important;
    }

    .print-document .print-doc-inner {
        padding: 0 !important;
    }

    .print-brand-header {
        padding-bottom: 12px !important;
        margin-bottom: 12px !important;
        gap: 16px !important;
    }

    .print-brand-logo {
        max-height: 44px !important;
        height: 44px !important;
    }

    .print-brand-name { font-size: 14pt !important; }
    .print-doc-no { font-size: 18pt !important; }
    .print-doc-meta { padding: 10px 12px !important; }

    .print-meta-grid {
        gap: 10px !important;
        margin-bottom: 12px !important;
    }

    .print-card { padding: 10px 12px !important; }

    .print-info-banner {
        padding: 8px 12px !important;
        margin-bottom: 10px !important;
        font-size: 10pt !important;
    }

    .print-table thead th {
        font-size: 7.5pt !important;
        padding: 7px 8px !important;
    }

    .print-table tbody td {
        font-size: 10pt !important;
        padding: 6px 8px !important;
    }

    .print-totals-panel {
        padding: 10px 12px !important;
        margin-top: 10px !important;
    }

    .print-totals-grand { font-size: 12pt !important; }

    .print-document--fit .print-totals-block,
    .print-document--fit .print-totals-panel {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-document--fit .print-items-table + .print-totals-block,
    .print-document--fit .print-items-table + .print-totals-panel {
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

    .print-signatures {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-document span[class*="rounded-full"],
    .print-document [class*="badge"] {
        background: #fff !important;
        border: 1px solid var(--print-border-strong) !important;
        color: var(--print-ink) !important;
    }
}
