@extends('layouts.public-gate-page')

@section('meta')
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Sipariş Takibi',
        'robots' => 'noindex, nofollow',
        'metaDescription' => 'Sipariş veya SSH takip kodunuzla siparişinizin hangi aşamada olduğunu öğrenin.',
    ])
@endsection

@section('active_portal', 'takip')
@section('hero_aria', 'Sipariş takibi')
@section('wrap_class', 'pub-wrap--md')

@section('hero')
    <p class="hero-kicker">Sipariş · SSH</p>
    <h1 class="hero-title">Takip kodu sorgula</h1>
    <p class="hero-lead">Sipariş numaranızı (SAT-…) veya SSH kodunuzu girerek sürecinizi görüntüleyin.</p>
@endsection

@section('footer_extra')
    <a href="{{ url('/katalog') }}">Malzeme kataloğu</a>
@endsection

@section('content')
    <div class="pub-box">
        <form method="POST" action="{{ url('/takip') }}" class="pub-form-row">
            @csrf
            <div class="field">
                <label for="code">Takip kodu</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code', $code) }}"
                    placeholder="Örn: SAT-2026-00042 veya SSH-2026-00003"
                    autocomplete="off"
                    autofocus
                    class="@error('code') is-error @enderror"
                >
                @error('code')<p class="err">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="pub-btn shrink-0">Sorgula</button>
        </form>
        <p class="pub-hint">Örnek: <code>SAT-2026-00042</code> veya <code>SSH-2026-00003</code></p>
    </div>

    @if($notFound)
        <div class="pub-alert pub-alert-warn">
            <strong>Kayıt bulunamadı</strong>
            “{{ $code }}” koduna ait sipariş veya SSH kaydı yok. Kodu kontrol edip tekrar deneyin.
        </div>
    @endif

    @if($result)
        <div class="pub-result" style="margin-top: 1.25rem;">
            @include('tracking.partials.result', ['result' => $result])
        </div>
    @endif
@endsection

@push('head')
    <style>.stage-line { height: 2px; flex: 1; min-width: 12px; }</style>
@endpush
