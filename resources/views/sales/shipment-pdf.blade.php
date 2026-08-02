@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentNumber }} - Sevkiyat Gönder Fişi</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 0; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #000; }
        h2 { font-size: 12px; margin: 0; color: #000; font-weight: 700; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0; color: #000; }
        .doc-no { font-size: 20px; font-weight: bold; color: #000; line-height: 1.1; }
        .section { width: 100%; margin-bottom: 14px; }
        .section-table { width: 100%; border-collapse: collapse; }
        .section-table td { vertical-align: top; padding: 0; width: 50%; color: #000; }
        .label { font-size: 9px; text-transform: uppercase; color: #000; font-weight: bold; margin-bottom: 5px; letter-spacing: 0.04em; }
        .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .items th, .items td { border: 1px solid #000; padding: 7px 8px; font-size: 10px; line-height: 1.4; color: #000; }
        .items th { background: #000; color: #fff; font-size: 9px; text-transform: uppercase; font-weight: 700; }
        .items td.center, .items th.center { text-align: center; }
        .check { display: inline-block; width: 12px; height: 12px; border: 1px solid #000; }
        .desc-list { margin: 4px 0 0; padding-left: 14px; list-style: disc; }
        .desc-list li { color: #000; font-size: 9px; line-height: 1.35; margin: 1px 0; }
        .muted { color: #000; font-size: 10px; margin: 2px 0; line-height: 1.4; }
        .right { text-align: right; }
        .signatures { border-top: 1px solid #000; padding-top: 12px; margin-top: 14px; page-break-inside: avoid; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sign-table td { width: 50%; vertical-align: top; padding-right: 16px; color: #000; }
        .sign-line { border-top: 1px solid #000; padding-top: 5px; margin-top: 36px; font-size: 10px; color: #000; }
        .notice { background: #fff; border-left: 3px solid #000; padding: 8px 10px; margin-bottom: 12px; font-size: 10px; color: #000; }
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
            </td>
            <td class="right">
                <h2>{{ $documentTitle ?? 'SEVKİYAT GÖNDER FİŞİ' }}</h2>
                <div class="doc-no">{{ $documentNumber }}</div>
                @if(!empty($documentDate))<p class="muted">{{ $documentDate->format('d.m.Y') }}</p>@endif
                @if(!empty($dueDate))<p class="muted">Teslim: {{ $dueDate->format('d.m.Y') }}</p>@endif
            </td>
        </tr>
    </table>
</div>

@if(!empty($documentNotice))
<div class="notice">{!! strip_tags($documentNotice, '<strong><b>') !!}</div>
@endif

<div class="section">
    <table class="section-table">
        <tr>
            <td>
                <div class="label">{{ $partyLabel ?? 'Teslimat Adresi' }}</div>
                <strong>{{ $partyName ?? '-' }}</strong>
                @if(!empty($partyAddress))<p class="muted">{{ $partyAddress }}</p>@endif
                @if(!empty($partyPhone))<p class="muted">{{ $partyPhone }}</p>@endif
                @if(!empty($partyPhone2))<p class="muted">{{ $partyPhone2 }}</p>@endif
                @if(!empty($partyEmail))<p class="muted">{{ $partyEmail }}</p>@endif
            </td>
            <td class="right">
                @if(!empty($personnelName))<p class="muted">Temsilci: <strong>{{ $personnelName }}</strong></p>@endif
                <p class="muted">Kalem: <strong>{{ count($items ?? []) }}</strong></p>
            </td>
        </tr>
    </table>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th>Ürün / Hizmet</th>
            <th style="width:60px;">Stok Kodu</th>
            <th class="center" style="width:32px;">Adet</th>
            <th class="center" style="width:24px;">✓</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items ?? [] as $i => $item)
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
            <td>{{ $item['sku'] ?? '—' }}</td>
            <td class="center"><strong>{{ $item['quantity'] ?? 0 }}</strong></td>
            <td class="center"><span class="check"></span></td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;">Sipariş kalemi yok.</td></tr>
        @endforelse
    </tbody>
</table>

@if(!empty($notes))
<div style="margin-bottom:8px; padding-top:6px; border-top:1px solid #e2e8f0;">
    <div class="label">Sipariş Notları</div>
    <p style="margin:0;">{{ $notes }}</p>
</div>
@endif

<div class="signatures">
    <p style="margin:0 0 8px;">Yukarıda listelenen ürün / hizmetleri <strong>eksiksiz</strong> ve <strong>hasarsız</strong> olarak teslim aldığımı beyan ederim.</p>
    <table class="sign-table">
        <tr>
            <td>
                <div class="label">Sevkiyat Görevlisi</div>
                <div class="sign-line">Ad Soyad / İmza</div>
                <div class="sign-line">Tarih</div>
            </td>
            <td>
                <div class="label">Müşteri (Teslim Alan)</div>
                <div class="sign-line">Ad Soyad / İmza</div>
                <div class="sign-line">Tarih</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
