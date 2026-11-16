<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => '3D Dolap Tasarla',
        'metaDescription' => 'Dolap modüllerini planda yerleştirin. 2D ve 3D aynı editörde düzenlenir.',
    ])
    <style>
        html, body { margin: 0; height: 100%; background: #1e293b; color: #fff; font: 14px/1.4 Montserrat, system-ui, sans-serif; }
        #plan-frame { position: fixed; inset: 0; width: 100%; height: 100%; border: 0; background: #e7e2d8; }
        #plan-wait, #plan-error {
            position: fixed; inset: 0; display: grid; place-items: center; text-align: center; padding: 24px;
        }
        #plan-error { display: none; }
        #plan-error a, #quote-open {
            color: #fff; background: #1a7a52; text-decoration: none; border: 0; border-radius: 999px;
            padding: 8px 14px; font-weight: 700; cursor: pointer;
        }
        #quote-open {
            position: fixed; right: 12px; bottom: 12px; z-index: 20;
        }
        #plan-toast {
            position: fixed; left: 50%; bottom: 16px; transform: translateX(-50%); z-index: 25;
            display: none; background: #b42318; color: #fff; border-radius: 999px; padding: 8px 14px; font-weight: 700;
        }
        #quote-modal {
            position: fixed; inset: 0; z-index: 30; display: none; place-items: center; background: rgba(15, 23, 42, .45);
        }
        #quote-modal.is-on { display: grid; }
        #quote-modal form {
            width: min(420px, calc(100% - 32px)); background: #fff; color: #122; border-radius: 16px; padding: 18px;
        }
        #quote-modal h2 { margin: 0 0 8px; font-size: 18px; }
        #quote-modal p, #quote-msg { margin: 0 0 12px; color: #526; font-size: 13px; }
        #quote-modal label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
        #quote-modal input { width: 100%; box-sizing: border-box; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 10px; padding: 10px; }
        #quote-modal .row { display: flex; justify-content: flex-end; gap: 8px; }
        #quote-modal button { border: 0; border-radius: 999px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
        #quote-send { background: #1a7a52; color: #fff; }
    </style>
</head>
<body>
    <p id="plan-wait">Plan açılıyor…</p>
    <div id="plan-error">
        <div>
            <p id="plan-error-text">Proje açılamadı.</p>
            <a href="{{ route('wardrobe.index') }}">Projelere dön</a>
        </div>
    </div>
    <iframe id="plan-frame" name="meemare-studio-plan" title="Dolap planı" hidden referrerpolicy="no-referrer-when-downgrade" allow="fullscreen; clipboard-read; clipboard-write"></iframe>
    <p id="plan-toast"></p>
    <button type="button" id="quote-open" hidden>Teklif</button>
    <div id="quote-modal">
        <form id="quote-form">
            <h2>Fiyat teklifi</h2>
            <p>Bu bir hazır fiyat değildir. Tasarım atölyeye gider, size dönüş yapılır.</p>
            <label>Ad<input name="name" required maxlength="120"></label>
            <label>Telefon<input name="phone" required maxlength="40"></label>
            <label>Not<input name="note" maxlength="2000"></label>
            <p id="quote-msg"></p>
            <div class="row">
                <button type="button" id="quote-close">Vazgeç</button>
                <button type="submit" id="quote-send">Gönder</button>
            </div>
        </form>
    </div>
    <script>
        window.WARDROBE = {
            quoteUrl: @json($quoteUrl),
            projectBase: @json(url('/dolap')),
            csrf: @json(csrf_token()),
            openplanSrc: @json($openplanSrc ?? ''),
            projectsUrl: @json(route('wardrobe.index')),
        };
    </script>
    <script type="module" src="{{ asset('js/studio-shell.js') }}?v=19"></script>
</body>
</html>
