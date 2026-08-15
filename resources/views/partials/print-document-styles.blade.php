:root {
    --print-font: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --print-ink: #0f172a;
    --print-muted: #475569;
    --print-faint: #64748b;
    --print-border: #e2e8f0;
    --print-border-strong: #94a3b8;
    --print-surface: #f8fafc;
    --print-surface-2: #f1f5f9;
    --print-head-bg: #0f172a;
    --print-head-text: #ffffff;
    --print-accent: #059669;
    --print-accent-soft: #d1fae5;
    --print-space: 16px;
    --print-space-sm: 11px;
    --print-space-xs: 7px;
    --print-radius: 8px;
}

.print-document,
.print-document * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Belge kabuğu ── */
.print-document {
    color: var(--print-ink);
    font-family: var(--print-font);
    font-size: 12.5px;
    font-weight: 500;
    line-height: 1.5;
    letter-spacing: 0.01em;
    background: #fff;
}

.print-document,
.print-document button,
.print-document input,
.print-document select,
.print-document textarea,
.print-document table,
.print-document th,
.print-document td,
.print-document nav,
.print-document label,
.print-document a,
.print-document p,
.print-document span,
.print-document div,
.print-document dl,
.print-document dt,
.print-document dd {
    font-family: var(--print-font);
}

.print-document .print-doc-inner {
    padding: 26px 28px;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.print-document :where(p, li, dd, dt, span, div, label, strong, em, a, td):not(.print-table thead *) {
    color: inherit;
}

.print-document :where(h1, h2, h3, h4) {
    color: var(--print-ink) !important;
    font-family: var(--print-font);
    font-weight: 700;
    letter-spacing: -0.01em;
}

.print-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--print-faint) !important;
    margin-bottom: 7px;
}

.print-section-title {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--print-ink) !important;
    margin-bottom: 10px;
    padding-bottom: 7px;
    border-bottom: 2px solid var(--print-accent);
}

.print-muted { color: var(--print-muted) !important; }

.print-document [class*="text-neutral"],
.print-document [class*="text-slate"],
.print-document [class*="text-gray"] {
    color: var(--print-ink) !important;
}

.print-document .print-table tbody [class*="text-neutral"],
.print-document .print-table tbody [class*="text-slate"],
.print-document .print-table tbody [class*="text-gray"] {
    color: var(--print-ink) !important;
}

.print-document .amount-negative,
.print-document [class*="text-red-"] {
    color: #dc2626 !important;
}

.print-document [class*="text-emerald-"] {
    color: #047857 !important;
}

.print-document [class*="text-blue-"] {
    color: #1d4ed8 !important;
}

/* ── Kurumsal başlık ── */
.print-brand-header {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    padding: 4px 0 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--print-border);
    position: relative;
}

.print-brand-header::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -1px;
    width: 72px;
    height: 3px;
    background: var(--print-accent);
    border-radius: 2px;
}

.print-brand-header__company {
    flex: 1;
    min-width: 0;
}

.print-brand-logo {
    height: 48px;
    max-height: 48px;
    width: auto;
    max-width: 160px;
    margin-bottom: 10px;
    object-fit: contain;
    object-position: left;
}

.print-brand-name {
    font-family: var(--print-font);
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--print-ink) !important;
    line-height: 1.15;
}

.print-brand-meta {
    font-size: 10.5px;
    font-weight: 500;
    line-height: 1.45;
    color: var(--print-muted) !important;
    margin-top: 6px;
    max-width: 34rem;
}

.print-brand-contacts {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    margin-top: 8px;
    font-size: 10.5px;
    font-weight: 500;
    color: var(--print-faint) !important;
}

.print-doc-meta {
    flex-shrink: 0;
    width: min(100%, 230px);
    text-align: right;
    background: linear-gradient(180deg, #ffffff 0%, var(--print-surface) 100%);
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    border-top: 3px solid var(--print-accent);
    padding: 14px 15px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.print-doc-type {
    display: block;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--print-accent) !important;
}

.print-doc-no {
    display: block;
    font-family: var(--print-font);
    font-size: 20px;
    font-weight: 700;
    line-height: 1.1;
    color: var(--print-ink) !important;
    margin-top: 6px;
    letter-spacing: -0.02em;
}

.print-doc-sub {
    display: block;
    font-size: 10.5px;
    font-weight: 500;
    color: var(--print-muted) !important;
    margin-top: 6px;
    line-height: 1.4;
}

/* ── Bilgi alanları ── */
.print-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.print-card {
    background: var(--print-surface);
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    padding: 12px 14px;
}

.print-card--meta p {
    font-size: 11.5px;
    line-height: 1.45;
    color: var(--print-muted) !important;
    margin-top: 5px;
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
    font-size: 11.5px;
    line-height: 1.45;
}

.print-card .print-party-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--print-ink) !important;
    line-height: 1.25;
    letter-spacing: -0.01em;
}

.print-kv-list {
    display: grid;
    gap: 6px;
}

.print-kv-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
    font-size: 11px;
    line-height: 1.4;
}

.print-kv-label {
    color: var(--print-muted) !important;
    font-weight: 500;
    flex-shrink: 0;
}

.print-kv-value {
    font-weight: 600;
    text-align: right;
    color: var(--print-ink) !important;
}

/* Sipariş özeti — fiş başlığı altında yatay kartlar */
@media screen {
    .print-document .sale-doc-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .print-document .sale-doc-summary {
            grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr));
        }
    }
    .print-document .sale-doc-summary__item {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.875rem 1rem;
        border: 1px solid var(--print-border);
        border-radius: 0.75rem;
        background: linear-gradient(180deg, #ffffff 0%, var(--print-surface) 100%);
        min-height: 4.25rem;
    }
    .print-document .sale-doc-summary__label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--print-faint) !important;
        line-height: 1.2;
    }
    .print-document .sale-doc-summary__value {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--print-ink) !important;
        line-height: 1.35;
    }
    .dark .print-document .sale-doc-summary__item {
        background: linear-gradient(180deg, #2a2a2a 0%, #262626 100%);
        border-color: #404040;
    }
    .dark .print-document .sale-doc-summary__label {
        color: #a3a3a3 !important;
    }
    .dark .print-document .sale-doc-summary__value {
        color: #f5f5f5 !important;
    }
}

@media print {
    .print-document .sale-doc-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
        gap: 8px;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--print-border);
    }
    .print-document .sale-doc-summary__item {
        padding: 7px 9px;
        border: 1px solid var(--print-border);
        border-radius: 4px;
        background: var(--print-surface);
    }
    .print-document .sale-doc-summary__label {
        display: block;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--print-muted) !important;
        margin-bottom: 2px;
    }
    .print-document .sale-doc-summary__value {
        margin: 0;
        font-size: 11px;
        font-weight: 600;
        color: var(--print-ink) !important;
        line-height: 1.3;
    }
}

@media screen {
    .print-document .sale-doc-meta {
        border: 1px solid var(--print-border);
        border-radius: 0.75rem;
        background: var(--print-surface);
        overflow: hidden;
    }
    .print-document .sale-doc-meta__item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--print-border);
    }
    .print-document .sale-doc-meta__item:last-child {
        border-bottom: none;
    }
    .print-document .sale-doc-meta__label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--print-faint) !important;
    }
    .print-document .sale-doc-meta__value {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--print-ink) !important;
        line-height: 1.35;
    }
    .dark .print-document .sale-doc-meta {
        background: #262626;
        border-color: #404040;
    }
    .dark .print-document .sale-doc-meta__item {
        border-bottom-color: #404040;
    }
    .dark .print-document .sale-doc-meta__label {
        color: #a3a3a3 !important;
    }
    .dark .print-document .sale-doc-meta__value {
        color: #f5f5f5 !important;
    }
}

@media print {
    .print-document .sale-doc-meta {
        border: 1px solid var(--print-border);
        background: var(--print-surface);
        border-radius: 4px;
    }
    .print-document .sale-doc-meta__item {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        padding: 5px 0;
        border-bottom: none;
        font-size: 11px;
        line-height: 1.35;
    }
    .print-document .sale-doc-meta__label {
        color: var(--print-muted) !important;
        font-size: 11px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
    }
    .print-document .sale-doc-meta__value {
        font-size: 11px;
        font-weight: 600;
        text-align: right;
        color: var(--print-ink) !important;
    }
}

.print-info-banner {
    background: var(--print-accent-soft) !important;
    border: 1px solid #a7f3d0 !important;
    border-left: 4px solid var(--print-accent) !important;
    border-radius: var(--print-radius) !important;
    padding: 10px 13px !important;
    font-size: 11.5px !important;
    font-weight: 500 !important;
    line-height: 1.45 !important;
    color: var(--print-ink) !important;
    margin-bottom: 14px;
}

.print-highlight-box {
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    background: linear-gradient(180deg, #ffffff 0%, var(--print-surface) 100%);
    padding: 16px 18px;
    margin-bottom: 14px;
    text-align: center;
}

.print-highlight-amount {
    font-size: 28px;
    font-weight: 700;
    color: var(--print-ink) !important;
    line-height: 1.1;
    margin-top: 4px;
    letter-spacing: -0.03em;
}

/* ── Tablo ── */
.print-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    table-layout: fixed;
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    overflow: hidden;
}

.print-table thead {
    background: var(--print-head-bg) !important;
    color: var(--print-head-text) !important;
}

.print-table thead th {
    background: var(--print-head-bg) !important;
    color: var(--print-head-text) !important;
    font-family: var(--print-font);
    font-weight: 600;
    font-size: 8.5px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 9px 10px !important;
    border: none !important;
    vertical-align: middle;
}

.print-table tbody td {
    color: var(--print-ink) !important;
    font-size: 11.5px;
    font-weight: 500;
    padding: 8px 10px !important;
    vertical-align: top;
    border: none !important;
    border-bottom: 1px solid var(--print-border) !important;
    background: #fff !important;
    word-wrap: break-word;
}

.print-table tbody tr:nth-child(even) td {
    background: var(--print-surface) !important;
}

.print-table tbody tr:last-child td {
    border-bottom: none !important;
}

.print-table tfoot td {
    padding: 9px 10px !important;
    font-size: 11.5px;
    font-weight: 700;
    border-top: 2px solid var(--print-ink) !important;
    background: var(--print-surface-2) !important;
    color: var(--print-ink) !important;
}

.print-table--compact thead th {
    padding: 6px 7px !important;
    font-size: 7.5px !important;
}

.print-table--compact tbody td {
    padding: 5px 7px !important;
    font-size: 10.5px !important;
}

.print-col-no { width: 4.5%; }
.print-col-name { width: auto; word-break: break-word; }
.print-col-price { width: 14%; white-space: nowrap; }
.print-col-qty { width: 7%; }
.print-col-kdv { width: 7%; }
.print-col-total { width: 14%; white-space: nowrap; }

.item-description-list {
    margin-top: 2px;
    font-size: 9.5px;
    line-height: 1.35;
    color: var(--print-muted) !important;
}

.item-description-list li {
    margin: 0;
}

/* ── Toplamlar ── */
.print-totals-panel {
    width: min(100%, 290px);
    margin-left: auto;
    margin-top: 14px;
    background: linear-gradient(180deg, #ffffff 0%, var(--print-surface) 100%);
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    border-top: 3px solid var(--print-accent);
    padding: 12px 14px;
}

.print-totals-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 14px;
    font-size: 11.5px;
    padding: 3px 0;
}

.print-totals-row span:first-child {
    color: var(--print-muted) !important;
    font-weight: 500;
}

.print-totals-row span:last-child {
    font-weight: 600;
    text-align: right;
    min-width: 92px;
    font-variant-numeric: tabular-nums;
}

.print-totals-grand {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 14px;
    margin-top: 8px;
    padding-top: 10px;
    border-top: 1px solid var(--print-border-strong);
    font-size: 14px;
    font-weight: 700;
}

.print-totals-grand span:last-child {
    text-align: right;
    min-width: 92px;
    font-variant-numeric: tabular-nums;
    color: var(--print-accent) !important;
}

.print-totals-row--after-grand { margin-top: 7px; }
.print-totals-row--status { margin-top: 5px; }

/* ── Notlar & imza ── */
.print-notes-block {
    margin-top: 14px;
    padding: 12px 14px;
    border: 1px solid var(--print-border);
    border-radius: var(--print-radius);
    background: var(--print-surface);
}

.print-notes-block p {
    font-size: 11.5px;
    line-height: 1.45;
    color: var(--print-muted) !important;
}

.print-footer-note {
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px dashed var(--print-border);
    font-size: 9.5px;
    color: var(--print-faint) !important;
    line-height: 1.45;
}

.print-signatures {
    border-top: 1px solid var(--print-border);
    padding-top: 16px;
    margin-top: 18px;
}

.print-signatures .sig-line {
    border-top: 1.5px solid var(--print-ink);
    padding-top: 6px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--print-muted) !important;
    margin-top: 44px;
}

.print-signatures--compact .sig-line {
    margin-top: 34px;
}

.print-document-footer {
    margin-top: 18px;
    padding-top: 12px;
    border-top: 1px solid var(--print-border);
    font-size: 9px;
    font-weight: 500;
    color: var(--print-faint) !important;
    line-height: 1.5;
}

.print-document-footer__brand {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0;
}

.print-document-footer__name {
    font-weight: 700;
    color: var(--print-ink) !important;
    letter-spacing: 0.02em;
}

.print-document-footer__sep {
    margin: 0 5px;
    opacity: 0.55;
}

.print-document-footer__meta {
    margin-top: 4px;
    font-variant-numeric: tabular-nums;
}

.print-document-footer__note {
    margin-top: 7px;
    font-style: italic;
    font-weight: 400;
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
    font-size: 9.5px !important;
    font-weight: 600 !important;
    padding: 2px 8px !important;
    border-radius: 999px !important;
}

.print-document table:not(.print-table) th,
.print-document table:not(.print-table) td {
    color: var(--print-ink) !important;
    border-color: var(--print-border) !important;
}

.print-check-box {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 1.5px solid var(--print-border-strong);
    border-radius: 2px;
    vertical-align: middle;
}

/* ── Ekran önizleme ── */
.print-document {
    border: 1px solid var(--print-border);
    border-radius: 12px;
    max-width: 210mm;
    margin: 0 auto;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 12px 32px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.print-section { margin-bottom: 14px; }
.print-section-lg { margin-bottom: 16px; }

.print-fit-target {
    transform-origin: top left;
}

.print-document--compact {
    font-size: 11.5px;
}

.print-document--compact .print-doc-inner {
    padding: 18px 20px;
}

@media print {
    .sale-personnel-notes-panel,
    #atolye-takibi {
        display: none !important;
    }

    .print-fit-target {
        transform: none !important;
        width: auto !important;
    }

    .print-document--fit {
        height: auto !important;
        overflow: visible !important;
    }

    .print-document {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        max-width: none !important;
        margin: 0 !important;
        font-size: 10pt !important;
        line-height: 1.4 !important;
        overflow: visible !important;
    }

    .print-document .print-doc-inner {
        padding: 0 !important;
    }

    .print-brand-header {
        padding-bottom: 10px !important;
        margin-bottom: 12px !important;
        gap: 14px !important;
    }

    .print-brand-header::after {
        width: 48px;
        height: 2.5px;
    }

    .print-brand-logo {
        max-height: 40px !important;
        height: 40px !important;
    }

    .print-brand-name { font-size: 13.5pt !important; }
    .print-doc-no { font-size: 15pt !important; }
    .print-doc-meta {
        padding: 9px 11px !important;
        width: 48mm;
        border-radius: 4px !important;
        box-shadow: none !important;
    }

    .print-meta-grid {
        gap: 8px !important;
        margin-bottom: 10px !important;
    }

    .print-card {
        padding: 8px 10px !important;
        border-radius: 4px !important;
    }

    .print-info-banner {
        padding: 7px 10px !important;
        margin-bottom: 8px !important;
        font-size: 9.5pt !important;
        border-radius: 4px !important;
    }

    .print-table {
        border-radius: 0 !important;
    }

    .print-table thead th {
        font-size: 7pt !important;
        padding: 6px 7px !important;
    }

    .print-table tbody td {
        font-size: 9.5pt !important;
        padding: 5px 7px !important;
    }

    .print-table--compact tbody td {
        font-size: 8.5pt !important;
        padding: 3px 6px !important;
    }

    .print-totals-panel {
        padding: 8px 10px !important;
        margin-top: 8px !important;
        border-radius: 4px !important;
    }

    .print-totals-grand { font-size: 11pt !important; }

    .print-highlight-amount { font-size: 18pt !important; }

    .print-notes-block {
        padding: 8px 10px !important;
        border-radius: 4px !important;
    }

    .print-document--fit .print-totals-block,
    .print-document--fit .print-totals-panel,
    .print-document--fit .print-document-footer,
    .print-document--fit .print-signatures {
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

    .sale-personnel-notes-panel,
    #atolye-takibi {
        display: none !important;
    }
}
