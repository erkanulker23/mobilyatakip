<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Giriş - Mobilya Takip</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); width: 100%; max-width: 360px; }
        h1 { margin: 0 0 1.5rem; font-size: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
        input { padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; }
        .error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
        .btn { padding: 0.6rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; width: 100%; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Mobilya Takip</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="remember"> Beni hatırla</label>
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
