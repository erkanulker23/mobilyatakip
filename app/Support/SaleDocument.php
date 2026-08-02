<?php

namespace App\Support;

use App\Models\Sale;

class SaleDocument
{
    public static function invoiceParams(Sale $sale): array
    {
        return [
            'documentTitle' => 'SİPARİŞ / SATIŞ FİŞİ',
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
            'subtotal' => $sale->subtotal,
            'kdvTotal' => $sale->kdvTotal,
            'grandTotal' => $sale->grandTotal,
            'paidAmount' => $sale->paidAmount ?? 0,
            'paidAmountLabel' => 'Kapora / Ödenen',
            'paymentStatus' => CustomerBalance::saleStatus($sale),
            'notes' => $sale->notes,
        ];
    }

    /** Sevkiyat gönder fişi — fiyat ve ödeme bilgisi içermez. */
    public static function shipmentParams(Sale $sale): array
    {
        return [
            'documentTitle' => 'SEVKİYAT GÖNDER FİŞİ',
            'documentNumber' => $sale->saleNumber,
            'documentDate' => $sale->saleDate,
            'dueDate' => $sale->dueDate,
            'partyLabel' => 'Teslimat Adresi',
            'partyName' => $sale->customer?->name ?? '-',
            'partyAddress' => $sale->customer ? full_address($sale->customer) : null,
            'partyPhone' => $sale->customer?->phone,
            'partyPhone2' => $sale->customer?->phone2 ?? null,
            'partyEmail' => $sale->customer?->email,
            'personnelName' => $sale->personnel?->name,
            'items' => collect($sale->items ?? [])->map(fn ($i) => [
                'name' => $i->productName ?? $i->product?->name ?? '-',
                'description' => $i->description ?? null,
                'quantity' => (int) ($i->quantity ?? 0),
                'sku' => $i->product?->sku ?? null,
            ])->values()->toArray(),
            'notes' => $sale->notes,
        ];
    }

    public static function extraInfoHtml(Sale $sale): string
    {
        $parts = [];
        if ($sale->dueDate) {
            $parts[] = '<p class="text-sm text-slate-600"><strong>Tahmini Teslim:</strong> '
                . $sale->dueDate->format('d.m.Y') . '</p>';
        }
        if ($sale->personnel) {
            $parts[] = '<p class="text-sm text-slate-600">Satış Temsilcisi: '
                . e($sale->personnel->name) . '</p>';
        }
        $paymentStatus = CustomerBalance::saleStatus($sale);
        $parts[] = '<p class="text-sm text-slate-600"><strong>Ödeme Durumu:</strong> '
            . e($paymentStatus['label']) . ' — ' . e($paymentStatus['description']) . '</p>';

        return implode('', $parts);
    }
}
