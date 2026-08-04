@php $company = \App\Models\Company::first(); @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satış Fişi: {{ $sale->saleNumber }}</title>
</head>
<body style="margin:0; padding:0; font-family: 'Montserrat', system-ui, -apple-system, sans-serif; background:#f1f5f9; color:#334155;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding: 24px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:12px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
<tr><td style="padding: 24px 32px; border-bottom: 1px solid #e2e8f0;">
    <h1 style="margin:0; font-size: 18px; color: #0f172a;">{{ $company?->name ?? 'Firma' }}</h1>
    <p style="margin: 8px 0 0; font-size: 14px; color: #64748b;">Satış Fişi</p>
</td></tr>
<tr><td style="padding: 24px 32px;">
    <p style="margin:0 0 16px; font-size: 15px;">Sayın <strong>{{ $sale->customer?->name ?? 'Müşterimiz' }}</strong>,</p>
    <p style="margin:0 0 20px; font-size: 15px; line-height: 1.5;">Satış fişiniz aşağıda yer almaktadır. Bizi tercih ettiğiniz için teşekkür ederiz.</p>

    @if($note)
    <p style="margin:0 0 20px; padding: 12px 16px; background:#f8fafc; border-left: 3px solid #10b981; font-size: 14px; line-height: 1.5;">{{ $note }}</p>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px;">
    <tr style="background: #f8fafc;"><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Fiş No</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->saleNumber }}</td></tr>
    <tr><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Tarih</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->saleDate?->format('d.m.Y') ?? '-' }}</td></tr>
    @if($sale->dueDate)
    <tr style="background: #f8fafc;"><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Tahmini Teslim</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->dueDate->format('d.m.Y') }}</td></tr>
    @endif
    @if($sale->personnel)
    <tr><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Satış Temsilcisi</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->personnel->name }}</td></tr>
    @endif
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 14px;">
    <tr style="background: #f8fafc;">
        <th align="left" style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px;">Ürün</th>
        <th align="center" style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px;">Adet</th>
        <th align="right" style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px;">Tutar</th>
    </tr>
    @foreach($sale->items as $item)
    <tr>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f1f5f9;">
            {{ $item->productName ?? $item->product?->name ?? '-' }}
            @if($item->description)
            <ul style="margin:4px 0 0; padding-left:14px; color:#64748b; font-size:12px;">
                @foreach(\App\Support\ItemDescription::lines($item->description) as $line)
                <li>{{ $line }}</li>
                @endforeach
            </ul>
            @endif
        </td>
        <td align="center" style="padding: 10px 12px; border-bottom: 1px solid #f1f5f9;">{{ $item->quantity }}</td>
        <td align="right" style="padding: 10px 12px; border-bottom: 1px solid #f1f5f9;">{{ number_format($item->lineTotal ?? 0, 2, ',', '.') }} ₺</td>
    </tr>
    @endforeach
    @if(!($sale->kdvIncluded ?? true))
    <tr>
        <td colspan="2" align="right" style="padding: 10px 12px; color: #64748b;">Ara Toplam</td>
        <td align="right" style="padding: 10px 12px;">{{ number_format($sale->subtotal ?? 0, 2, ',', '.') }} ₺</td>
    </tr>
    @endif
    @if(!($sale->kdvIncluded ?? true))
    <tr>
        <td colspan="2" align="right" style="padding: 10px 12px; color: #64748b;">KDV</td>
        <td align="right" style="padding: 10px 12px;">{{ number_format($sale->kdvTotal ?? 0, 2, ',', '.') }} ₺</td>
    </tr>
    @endif
    <tr>
        <td colspan="2" align="right" style="padding: 12px; font-weight: 600; border-top: 2px solid #e2e8f0;">Genel Toplam</td>
        <td align="right" style="padding: 12px; font-weight: 700; font-size: 16px; color: #047857; border-top: 2px solid #e2e8f0;">{{ number_format($sale->grandTotal ?? 0, 2, ',', '.') }} ₺</td>
    </tr>
    @if(($sale->paidAmount ?? 0) > 0)
    <tr>
        <td colspan="2" align="right" style="padding: 10px 12px; color: #64748b;">Kapora / Ödenen</td>
        <td align="right" style="padding: 10px 12px;">{{ number_format($sale->paidAmount, 2, ',', '.') }} ₺</td>
    </tr>
    <tr>
        <td colspan="2" align="right" style="padding: 10px 12px; font-weight: 600;">Kalan</td>
        <td align="right" style="padding: 10px 12px; font-weight: 600;">{{ number_format(($sale->grandTotal ?? 0) - $sale->paidAmount, 2, ',', '.') }} ₺</td>
    </tr>
    @endif
    @php $mailPaymentStatus = \App\Support\CustomerBalance::saleStatus($sale); @endphp
    <tr style="background: #f8fafc;">
        <td colspan="2" align="right" style="padding: 12px; font-weight: 600; border-top: 2px solid #e2e8f0;">Ödeme Durumu</td>
        <td align="right" style="padding: 12px; font-weight: 600; border-top: 2px solid #e2e8f0;">{{ $mailPaymentStatus['label'] }} — {{ $mailPaymentStatus['description'] }}</td>
    </tr>
    </table>

    @if($sale->notes)
    <p style="margin: 20px 0 0; font-size: 13px; color: #64748b;"><strong>Not:</strong> {{ $sale->notes }}</p>
    @endif
</td></tr>
<tr><td style="padding: 16px 32px; background: #f8fafc; font-size: 12px; color: #64748b;">
    {{ $company?->name ?? '' }}@if($company?->phone) · {{ $company->phone }}@endif @if($company?->email) · {{ $company->email }}@endif
</td></tr>
</table>
</td></tr></table>
</body>
</html>
