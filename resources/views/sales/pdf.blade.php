@php
    $company = \App\Models\Company::first();
    $params = \App\Support\SaleDocument::invoiceParams($sale);
    extract($params);
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentNumber }} - Sipariş</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #334155; margin: 0; padding: 0; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0f172a; }
        h2 { font-size: 12px; margin: 0; color: #475569; font-weight: 600; }
        .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0; }
        .doc-no { font-size: 20px; font-weight: bold; color: #047857; line-height: 1.1; }
        .party-row { width: 100%; margin-bottom: 14px; }
        .party-box { width: 48%; display: inline-block; vertical-align: top; }
        .party-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 5px; letter-spacing: 0.04em; }
        .items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items th, .items td { border: 1px solid #e2e8f0; padding: 7px 8px; font-size: 10px; line-height: 1.4; }
        .items th { background: #f8fafc; font-size: 9px; text-transform: uppercase; color: #64748b; }
        .items td.right, .items th.right { text-align: right; }
        .items td.center, .items th.center { text-align: center; }
        .totals { width: 240px; margin-left: auto; margin-top: 12px; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; font-size: 10px; }
        .totals .grand { font-size: 13px; font-weight: bold; border-top: 2px solid #cbd5e1; padding-top: 6px; }
        .notes { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
        .muted { color: #64748b; font-size: 10px; margin: 2px 0; line-height: 1.4; }
        .desc-list { margin: 4px 0 0; padding-left: 14px; list-style: disc; }
        .desc-list li { color: #64748b; font-size: 9px; line-height: 1.35; margin: 1px 0; }
        .notice { background: #fffbeb; border-left: 3px solid #d97706; padding: 8px 10px; margin-bottom: 12px; font-size: 10px; color: #92400e; font-weight: 600; }
    </style>
</head>
<body>
<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <h1>{{ $company?->name ?? 'Firma Adı' }}</h1>
                @if($company?->address)<p class="muted">{{ $company->address }}</p>@endif
                @if($company?->phone)<p class="muted">{{ $company->phone }}</p>@endif
                @if($company?->email)<p class="muted">{{ $company->email }}</p>@endif
            </td>
            <td style="text-align: right;">
                <h2>{{ $documentTitle }}</h2>
                <div class="doc-no">{{ $documentNumber }}</div>
                @if(isset($documentDate) && $documentDate)
                <p class="muted">{{ $documentDate->format('d.m.Y') }}</p>
                @endif
            </td>
        </tr>
    </table>
</div>

@if(!empty($documentNotice))
<div class="notice">{!! strip_tags($documentNotice, '<strong><b>') !!}</div>
@endif

<div class="party-row">
    <div class="party-box">
        <div class="party-label">{{ $partyLabel ?? 'Alıcı' }}</div>
        <strong>{{ $partyName ?? '-' }}</strong>
        @if(!empty($partyAddress))<p class="muted">{{ $partyAddress }}</p>@endif
        @if(!empty($partyPhone))<p class="muted">{{ $partyPhone }}</p>@endif
        @if(!empty($partyEmail))<p class="muted">{{ $partyEmail }}</p>@endif
        @if(!empty($partyTax))<p class="muted">Vergi: {{ $partyTax }}</p>@endif
    </div>
    @if(!empty($extraInfo))
    <div class="party-box" style="text-align: right;">
        {!! $extraInfo !!}
    </div>
    @endif
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:24px;">#</th>
            <th>Ürün / Açıklama</th>
            <th class="right" style="width:70px;">Birim</th>
            <th class="center" style="width:36px;">Adet</th>
            @if(!empty($showKdv))<th class="right" style="width:40px;">KDV</th>@endif
            <th class="right" style="width:70px;">Toplam</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $item['name'] ?? '-' }}
                @if(!empty($item['description']))
                <ul class="desc-list">
                    @foreach(\App\Support\ItemDescription::lines($item['description']) as $line)
                    <li>{{ $line }}</li>
                    @endforeach
                </ul>
                @endif
            </td>
            <td class="right">{{ number_format($item['unitPrice'] ?? 0, 0, ',', '.') }} ₺</td>
            <td class="center">{{ $item['quantity'] ?? 0 }}</td>
            @if(!empty($showKdv))<td class="right">%{{ number_format($item['kdvRate'] ?? 0, 0) }}</td>@endif
            <td class="right">{{ number_format($item['lineTotal'] ?? 0, 0, ',', '.') }} ₺</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        @if(isset($subtotal))
        <tr><td class="muted">Ara Toplam:</td><td style="text-align:right;">{{ number_format($subtotal ?? 0, 0, ',', '.') }} ₺</td></tr>
        @endif
        @if(isset($kdvTotal))
        <tr><td class="muted">KDV Toplam:</td><td style="text-align:right;">{{ number_format($kdvTotal ?? 0, 0, ',', '.') }} ₺</td></tr>
        @endif
        <tr class="grand"><td>Genel Toplam:</td><td style="text-align:right; color:#047857;">{{ number_format($grandTotal ?? 0, 0, ',', '.') }} ₺</td></tr>
        @if(isset($paidAmount) && ($paidAmount ?? 0) > 0)
        <tr><td class="muted">{{ $paidAmountLabel ?? 'Kapora / Ödenen' }}:</td><td style="text-align:right; color:#047857;">{{ number_format($paidAmount ?? 0, 0, ',', '.') }} ₺</td></tr>
        <tr><td class="muted">Kalan:</td><td style="text-align:right;">{{ number_format(($grandTotal ?? 0) - ($paidAmount ?? 0), 0, ',', '.') }} ₺</td></tr>
        @endif
        @php $pdfPaymentStatus = $paymentStatus ?? \App\Support\CustomerBalance::statusFromTotals((float) ($grandTotal ?? 0), (float) ($paidAmount ?? 0)); @endphp
        <tr><td class="muted">Durum:</td><td style="text-align:right; font-weight:600;">{{ $pdfPaymentStatus['label'] }}</td></tr>
    </table>
</div>

@if(!empty($notes))
<div class="notes">
    <div class="party-label">Notlar</div>
    <p style="margin:0;">{{ $notes }}</p>
</div>
@endif
</body>
</html>
