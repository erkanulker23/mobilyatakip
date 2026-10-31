<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => $tool['title'],
        'metaDescription' => $tool['lead'],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: Montserrat, system-ui, sans-serif; background: #f3f1ec; color: #17211c; }
        .bar { height: 52px; display: flex; align-items: center; gap: 12px; padding: 0 14px; background: #fffcf8; border-bottom: 1px solid #e4e1da; }
        .bar a { color: #1f6b4a; text-decoration: none; font-size: 13px; font-weight: 600; }
        .bar strong { font-size: 14px; }
        .bar span { color: #5e6a64; font-size: 12px; }
        .grow { flex: 1; }
        iframe { width: 100%; height: calc(100% - 52px); border: 0; background: #fff; }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('login') }}">Giriş</a>
        <a href="{{ url('/dolap') }}">Dolap</a>
        <strong>{{ $tool['title'] }}</strong>
        <span class="grow">{{ $tool['lead'] }}</span>
        <a href="{{ $tool['source'] }}" target="_blank" rel="noopener">{{ $tool['credit'] }}</a>
        <a href="{{ $tool['src'] }}" target="_blank" rel="noopener">Yeni sekme</a>
    </div>
    <iframe src="{{ $tool['src'] }}" title="{{ $tool['title'] }}" referrerpolicy="no-referrer-when-downgrade"></iframe>
</body>
</html>
