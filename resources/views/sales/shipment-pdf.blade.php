@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentNumber }} - Sevkiyat Gönder Fişi</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #334155; margin: 0; padding: 0; line-height: 1.35; }
        h1 { font-size: 13px; margin: 0 0 2px; color: #0f172a; }
        h2 { font-size: 10px; margin: 0; color: #475569; font-weight: 600; }
        .header { border-bottom: 2px solid #171717; padding-bottom: 6px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0; }
        .doc-no { font-size: 15px; font-weight: bold; color: #1e293b; }
        .section { width: 100%; margin-bottom: 10px; }
        .section-table { width: 100%; border-collapse: collapse; }
        .section-table td { vertical-align: top; padding: 0; width: 50%; }
        .label { font-size: 7px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .items th, .items td { border: 1px solid #e2e8f0; padding: 4px 5px; font-size: 8px; }
        .items th { background: #171717; color: #fff; font-size: 7px; text-transform: uppercase; }
        .items td.center, .items th.center { text-align: center; }
        .check { display: inline-block; width: 10px; height: 10px; border: 1px solid #64748b; }
        .desc { color: #64748b; font-size: 7px; }
        .muted { color: #64748b; font-size: 8px; margin: 1px 0; }
        .right { text-align: right; }
        .signatures { border-top: 1px solid #d4d4d4; padding-top: 10px; margin-top: 10px; page-break-inside: avoid; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .sign-table td { width: 50%; vertical-align: top; padding-right: 12px; }
        .sign-line { border-top: 1px solid #171717; padding-top: 4px; margin-top: 28px; font-size: 8px; color: #525252; }
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
                @if(!empty($item['description']))<br><span class="desc">{{ $item['description'] }}</span>@endif
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
