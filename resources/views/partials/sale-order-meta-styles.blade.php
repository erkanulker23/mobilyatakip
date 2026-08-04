@push('head')
<style>
.sale-order-meta {
    --som-border: #e5e5e5;
    --som-bg: #ffffff;
    --som-label: #737373;
    --som-value: #171717;
    --som-icon-bg: #f5f5f5;
    --som-icon: #525252;
}
.dark .sale-order-meta {
    --som-border: #404040;
    --som-bg: #171717;
    --som-label: #a3a3a3;
    --som-value: #f5f5f5;
    --som-icon-bg: #262626;
    --som-icon: #d4d4d4;
}
.sale-order-meta__card {
    border: 1px solid var(--som-border);
    border-radius: 1rem;
    background: var(--som-bg);
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.sale-order-meta__header {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--som-border);
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.02) 0%, transparent 100%);
}
.dark .sale-order-meta__header {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, transparent 100%);
}
.sale-order-meta__title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--som-value);
    letter-spacing: -0.01em;
}
.sale-order-meta__subtitle {
    font-size: 0.6875rem;
    color: var(--som-label);
    margin-top: 0.125rem;
}
.sale-order-meta__grid {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 0;
}
@media (min-width: 640px) {
    .sale-order-meta__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .sale-order-meta__grid {
        grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
    }
}
.sale-order-meta__item {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--som-border);
    min-height: 4.75rem;
}
@media (min-width: 640px) {
    .sale-order-meta__item {
        border-bottom: none;
        border-right: 1px solid var(--som-border);
    }
    .sale-order-meta__grid .sale-order-meta__item:last-child {
        border-right: none;
    }
}
.sale-order-meta__icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--som-icon-bg);
    color: var(--som-icon);
}
.sale-order-meta__icon svg {
    width: 1.125rem;
    height: 1.125rem;
}
.sale-order-meta__icon--date { background: #eff6ff; color: #2563eb; }
.sale-order-meta__icon--termin { background: #fffbeb; color: #d97706; }
.sale-order-meta__icon--person { background: #f5f3ff; color: #7c3aed; }
.sale-order-meta__icon--payment { background: #fef2f2; color: #dc2626; }
.sale-order-meta__icon--delivery { background: #eef2ff; color: #4f46e5; }
.sale-order-meta__icon--measure { background: #fffbeb; color: #b45309; }
.dark .sale-order-meta__icon--date { background: rgba(37, 99, 235, 0.15); color: #93c5fd; }
.dark .sale-order-meta__icon--termin { background: rgba(217, 119, 6, 0.15); color: #fcd34d; }
.dark .sale-order-meta__icon--person { background: rgba(124, 58, 237, 0.15); color: #c4b5fd; }
.dark .sale-order-meta__icon--payment { background: rgba(220, 38, 38, 0.15); color: #fca5a5; }
.dark .sale-order-meta__icon--delivery { background: rgba(79, 70, 229, 0.15); color: #a5b4fc; }
.dark .sale-order-meta__icon--measure { background: rgba(180, 83, 9, 0.15); color: #fcd34d; }
.sale-order-meta__body {
    min-width: 0;
    flex: 1;
}
.sale-order-meta__label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--som-label);
    line-height: 1.2;
}
.sale-order-meta__value {
    margin: 0.35rem 0 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--som-value);
    line-height: 1.35;
    word-break: break-word;
}
.sale-order-meta__value--badge {
    margin-top: 0.5rem;
}
@media (max-width: 639px) {
    .sale-order-meta__grid .sale-order-meta__item:last-child {
        border-bottom: none;
    }
}
</style>
@endpush
