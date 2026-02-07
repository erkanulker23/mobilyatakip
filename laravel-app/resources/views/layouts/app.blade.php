<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mobilya Takip')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #f5f5f5; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #1e293b; color: #e2e8f0; padding: 1rem 0; }
        .sidebar a { display: block; padding: 0.6rem 1.2rem; color: #cbd5e1; text-decoration: none; }
        .sidebar a:hover { background: #334155; color: #fff; }
        .sidebar .brand { padding: 1rem 1.2rem; font-weight: 700; font-size: 1.1rem; }
        .main { flex: 1; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; color: #fff; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; }
        .error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
        input, select, textarea { padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
    </style>
</head>
<body>
    <div class="app">
        <nav class="sidebar">
            <div class="brand">Mobilya Takip</div>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('customers.index') }}">Müşteriler</a>
            <a href="{{ route('suppliers.index') }}">Tedarikçiler</a>
            <a href="{{ route('products.index') }}">Ürünler</a>
            <a href="{{ route('warehouses.index') }}">Depolar</a>
            <a href="{{ route('stock.index') }}">Stok</a>
            <a href="{{ route('quotes.index') }}">Teklifler</a>
            <a href="{{ route('sales.index') }}">Satışlar</a>
            <a href="{{ route('purchases.index') }}">Alışlar</a>
            <a href="{{ route('customer-payments.create') }}">Ödeme Al</a>
            <a href="{{ route('supplier-payments.create') }}">Ödeme Yap</a>
            <a href="{{ route('kasa.index') }}">Kasa</a>
            <a href="{{ route('expenses.index') }}">Giderler</a>
            <a href="{{ route('reports.index') }}">Raporlar</a>
            <a href="{{ route('service-tickets.index') }}">SSH</a>
            <a href="{{ route('personnel.index') }}">Personel</a>
            <form method="POST" action="{{ route('logout') }}" style="padding: 0.6rem 1.2rem;">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%;text-align:left;background:transparent;color:#cbd5e1;border:none;cursor:pointer;padding:0;">Çıkış</button>
            </form>
        </nav>
        <main class="main">
            @if(session('success'))
                <div class="card" style="background:#dcfce7;color:#166534;">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="card" style="background:#fee2e2;color:#991b1b;">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
