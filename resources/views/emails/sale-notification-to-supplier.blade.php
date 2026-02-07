@php $company = \App\Models\Company::first(); @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş: {{ $sale->saleNumber }}</title>
</head>
<body style="margin:0; padding:0; font-family: system-ui, -apple-system, sans-serif; background:#f1f5f9; color:#334155;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding: 24px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:12px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
<tr><td style="padding: 24px 32px; border-bottom: 1px solid #e2e8f0;">
    <h1 style="margin:0; font-size: 18px; color: #0f172a;">{{ $company?->name ?? 'Firma' }}</h1>
    <p style="margin: 8px 0 0; font-size: 14px; color: #64748b;">Sipariş</p>
</td></tr>
<tr><td style="padding: 24px 32px;">
    <p style="margin:0 0 16px; font-size: 15px;">Sayın <strong>{{ $supplier->name }}</strong>,</p>
    <p style="margin:0 0 20px; font-size: 15px; line-height: 1.5;">Aşağıdaki satış faturası oluşturulmuştur. Sizin tedarik ettiğiniz ürünlere ihtiyacımız bulunmaktadır.</p>

    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0; border-radius: 8px;">
    <tr style="background: #f8fafc;"><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Fatura No</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->saleNumber }}</td></tr>
    <tr><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Tarih</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->saleDate?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr style="background: #f8fafc;"><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Müşteri</td><td style="padding: 12px 16px; font-size: 14px;">{{ $sale->customer?->name ?? '-' }}</td></tr>
    <tr><td style="padding: 12px 16px; font-weight: 600; font-size: 14px;">Genel Toplam</td><td style="padding: 12px 16px; font-size: 14px; font-weight: 600;">{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }} ₺</td></tr>
    </table>

    <p style="margin: 20px 0 0; font-size: 13px; color: #64748b;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</td></tr>
<tr><td style="padding: 16px 32px; background: #f8fafc; font-size: 12px; color: #64748b;">
    {{ $company?->name ?? '' }} @if($company?->email) · {{ $company->email }} @endif
</td></tr>
</table>
</td></tr></table>
</body>
</html>
