<?php

namespace App\Support;

use App\Models\Quote;
use Illuminate\Support\Str;

class QuoteDocument
{
    public static function downloadFilename(Quote $quote): string
    {
        $customer = Str::slug(trim((string) ($quote->customer?->name ?? '')), '-');
        if ($customer === '') {
            $customer = 'musteri';
        }
        $number = preg_replace('/[^a-zA-Z0-9\-]/', '-', (string) ($quote->quoteNumber ?? ''));
        $number = trim($number, '-');

        return $customer.'-teklif-'.($number !== '' ? $number : 'belge').'.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    public static function pdfParams(Quote $quote): array
    {
        $quoteIssuedAt = $quote->createdAt;
        $quoteValidUntil = $quoteIssuedAt?->copy()->addDays(3);
        $preparer = $quote->personnel?->name ?? QuoteCreator::displayNameForQuote($quote) ?? '-';

        $extraInfo = '<p class="muted"><strong>Teklif Tarihi:</strong> '.e($quoteIssuedAt?->format('d.m.Y') ?? '-').'</p>'
            .'<p class="muted"><strong>Son Geçerlilik:</strong> '.e($quoteValidUntil?->format('d.m.Y') ?? '-').'</p>'
            .'<p class="muted"><strong>Teklifi Hazırlayan:</strong> '.e($preparer).'</p>';
        if ($quote->branch) {
            $extraInfo .= '<p class="muted"><strong>Şube:</strong> '.e($quote->branch->name).'</p>';
        }

        $drawingFiles = $quote->drawingFiles ?? [];
        $hasDrawings = is_array($drawingFiles) && count($drawingFiles) > 0;

        return [
            'documentTitle' => 'TEKLİF',
            'documentSubtitle' => 'Geçerlilik: '.($quoteValidUntil?->format('d.m.Y') ?? '—').' · Fatura değildir',
            'documentNumber' => $quote->quoteNumber,
            'documentDate' => $quoteIssuedAt,
            'partyLabel' => 'Müşteri',
            'partyName' => $quote->customer?->name ?? '-',
            'partyAddress' => $quote->customer ? full_address($quote->customer) : null,
            'partyPhone' => $quote->customer?->phone,
            'partyEmail' => $quote->customer?->email,
            'partyTax' => ($quote->customer?->taxNumber
                ? $quote->customer->taxNumber.($quote->customer->taxOffice ? ' / '.$quote->customer->taxOffice : '')
                : null),
            'extraInfo' => $extraInfo,
            'items' => $quote->items->map(fn ($i) => [
                'name' => $i->product?->name ?? $i->productName,
                'description' => $i->description,
                'unitPrice' => $i->unitPrice,
                'quantity' => $i->quantity,
                'kdvRate' => $i->kdvRate,
                'lineTotal' => $i->lineTotal,
            ])->toArray(),
            'showKdv' => true,
            'kdvIncluded' => (bool) ($quote->kdvIncluded ?? true),
            'subtotal' => $quote->subtotal,
            'kdvTotal' => $quote->kdvTotal,
            'discount' => $quote->generalDiscountApplied(),
            'grandTotal' => $quote->grandTotal,
            'notes' => $quote->notes,
            'termsTitle' => 'TEKLİF VE SİPARİŞ KOŞULLARI',
            'terms' => QuotePrintTerms::items(),
            'hasDrawingFiles' => $hasDrawings,
        ];
    }
}
