<?php

namespace App\Support;

use App\Models\Sale;

class SaleDocument
{
    public static function invoiceParams(Sale $sale): array
    {
        return [
            'documentTitle' => 'SİPARİŞ FİŞİ',
            'documentNumber' => $sale->saleNumber,
            'documentDate' => $sale->saleDate,
            'partyLabel' => 'Müşteri',
            'partyName' => $sale->customer?->name ?? '-',
            'partyAddress' => $sale->customer ? full_address($sale->customer) : null,
            'partyPhone' => $sale->customer?->phone,
            'partyEmail' => $sale->customer?->email,
            'partyTax' => ($sale->customer?->taxNumber
                ? $sale->customer->taxNumber . ($sale->customer->taxOffice ? ' / ' . $sale->customer->taxOffice : '')
                : null),
            'extraInfo' => self::extraInfoHtml($sale),
            'items' => collect($sale->items ?? [])->map(fn ($i) => [
                'name' => $i->productName ?? $i->product?->name ?? '-',
                'description' => $i->description ?? null,
                'unitPrice' => $i->unitPrice ?? 0,
                'quantity' => $i->quantity ?? 0,
                'kdvRate' => $i->kdvRate ?? 18,
                'lineTotal' => $i->lineTotal ?? 0,
            ])->toArray(),
            'showKdv' => true,
            'kdvIncluded' => (bool) ($sale->kdvIncluded ?? true),
            'subtotal' => $sale->subtotal,
            'kdvTotal' => $sale->kdvTotal,
            'grandTotal' => $sale->grandTotal,
            'paidAmount' => $sale->paidAmount ?? 0,
            'paidAmountLabel' => 'Kapora / Ödenen',
            'paymentStatus' => CustomerBalance::saleStatus($sale),
            'notes' => $sale->notes,
            'showSignatures' => true,
            'footerNote' => 'Sipariş fişi — fiyatlar KDV dahil/hariç bilgisi toplam satırında belirtilmiştir.',
            'documentNotice' => ($sale->needsFinalMeasurement ?? false)
                ? '<strong>KESİN ÖLÇÜ BEKLİYOR</strong> — Bu sipariş için saha ölçüsü alınacaktır. Üretim ve teslimat kesin ölçü sonrası planlanır.'
                : null,
        ];
    }

    /** Sevkiyat / atölye fişleri — fiyat ve ödeme bilgisi içermez. */
    public static function slipParams(Sale $sale, string $variant = 'shipment'): array
    {
        $titles = [
            'shipment' => 'SEVKİYAT FİŞİ',
            'koltuk' => 'KOLTUK ATÖLYE FİŞİ',
            'mobilya' => 'MOBİLYA ATÖLYESİ FİŞİ',
        ];

        if (! isset($titles[$variant])) {
            $variant = 'shipment';
        }

        return [
            'documentTitle' => $titles[$variant],
            'slipVariant' => $variant,
            'documentNumber' => $sale->saleNumber,
            'documentDate' => $sale->saleDate,
            'dueDate' => $sale->dueDate,
            'partyLabel' => $variant === 'shipment' ? 'Teslimat Adresi' : 'Müşteri / Sipariş',
            'partyName' => $sale->customer?->name ?? '-',
            'partyAddress' => $sale->customer ? full_address($sale->customer) : null,
            'partyPhone' => $sale->customer?->phone,
            'partyPhone2' => $sale->customer?->phone2 ?? null,
            'partyEmail' => $sale->customer?->email,
            'personnelName' => $sale->personnel?->name,
            'documentNotice' => ($sale->needsFinalMeasurement ?? false)
                ? '<strong>KESİN ÖLÇÜYE GİDİLECEK</strong> — '
                    . ($variant === 'shipment'
                        ? 'Teslimattan önce kesin ölçü alınacaktır.'
                        : 'Üretim kesin ölçü sonrası planlanır.')
                : null,
            'items' => collect($sale->items ?? [])->map(fn ($i) => [
                'name' => self::shipmentItemName($i),
                'description' => $i->description ?? null,
                'quantity' => (int) ($i->quantity ?? 0),
                'sku' => $i->product?->sku ?? null,
            ])->values()->toArray(),
            'notes' => $sale->notes,
            'showCheckColumn' => $variant === 'shipment',
        ];
    }

    /** @deprecated Use slipParams($sale, 'shipment') */
    public static function shipmentParams(Sale $sale): array
    {
        return self::slipParams($sale, 'shipment');
    }

    /** Ürün adından fiyat parantezlerini kaldırır: "Koltuk (75.000 ₺)" → "Koltuk" */
    public static function shipmentItemName(object $item): string
    {
        $name = $item->product?->name
            ?? (filled($item->productName ?? null) ? $item->productName : null)
            ?? '-';

        return self::stripPriceFromLabel((string) $name);
    }

    public static function stripPriceFromLabel(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '-';
        }

        $clean = preg_replace('/\s*\([0-9][0-9.,\s]*(?:₺|TL)?\s*\)\s*$/ui', '', $trimmed) ?? $trimmed;
        $clean = trim($clean);

        return $clean !== '' ? $clean : $trimmed;
    }

    public static function extraInfoHtml(Sale $sale): string
    {
        $rows = [];

        if ($sale->dueDate) {
            $rows[] = ['Tahmini Teslim', $sale->dueDate->format('d.m.Y')];
        }
        if ($sale->personnel) {
            $rows[] = ['Satış Temsilcisi', $sale->personnel->name];
        }
        if ($sale->needsFinalMeasurement ?? false) {
            $rows[] = ['Kesin Ölçü', 'Evet — saha ölçüsü alınacak'];
        }

        $paymentStatus = CustomerBalance::saleStatus($sale);
        $rows[] = ['Ödeme Durumu', $paymentStatus['label'] . ' (' . $paymentStatus['description'] . ')'];

        $html = '<div class="print-kv-list">';
        foreach ($rows as [$label, $value]) {
            $html .= '<div class="print-kv-row"><span class="print-kv-label">' . e($label) . '</span><span class="print-kv-value">' . e($value) . '</span></div>';
        }

        return $html . '</div>';
    }
}
