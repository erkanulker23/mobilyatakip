@extends('layouts.public-gate-page')

@section('meta')
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => $manufacturer['name'].' · Malzeme Kataloğu',
        'metaDescription' => $manufacturer['name'].' malzeme ve renk kategorileri.',
    ])
@endsection

@section('active_portal', 'katalog')
@section('hero_aria', $manufacturer['name'])

@section('hero')
    <p class="hero-kicker">Malzeme kataloğu</p>
    <h1 class="hero-title">{{ $manufacturer['name'] }}</h1>
    <p class="hero-lead">Ürün kategorilerini seçerek renk ve dekor örneklerini görüntüleyin.</p>
@endsection

@section('footer_extra')
    <a href="{{ url('/takip') }}">Sipariş takibi</a>
@endsection

@section('content')
    <nav class="pub-crumb" aria-label="Konum">
        <a href="{{ route('catalog.index') }}">Katalog</a>
        <span aria-hidden="true">/</span>
        <span>{{ $manufacturer['name'] }}</span>
    </nav>

    @php
        $featured = collect($categories)->firstWhere('slug', 'glossmax-pro');
        $mainCats = collect($categories)->reject(fn ($c) => ($c['slug'] ?? '') === 'glossmax-pro')->values();
    @endphp

    @if($featured)
        <div style="margin-bottom: 1.5rem;">
            <div class="pub-card-kicker" style="margin-bottom: 0.65rem;">PDF katalog</div>
            <a href="{{ route('catalog.category', [$manufacturer['slug'], $featured['slug']]) }}"
               class="pub-card pub-card--featured">
                <div class="pub-card-media">
                    @php
                        $hero = public_path('catalog/'.$manufacturer['slug'].'/'.$featured['slug'].'/hero.webp');
                        $heroUrl = file_exists($hero) ? asset('catalog/'.$manufacturer['slug'].'/'.$featured['slug'].'/hero.webp') : null;
                    @endphp
                    @if($heroUrl)
                        <img src="{{ $heroUrl }}" alt="{{ $featured['name'] }}">
                    @else
                        <div class="pub-card-media--placeholder">{{ $featured['name'] }}</div>
                    @endif
                </div>
                <div class="pub-card-body" style="display:flex;flex-direction:column;justify-content:center;">
                    <h2 class="pub-card-title">{{ $featured['name'] }}</h2>
                    <p class="pub-card-text">Resmi katalogdaki 12 high gloss renk — Yalın ve Doğal seriler. Teknik özellikler ve PDF indirme.</p>
                    <span class="pub-card-link">
                        Koleksiyonu aç
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>
        </div>
    @endif

    <div class="pub-grid pub-grid--3">
        @foreach($mainCats as $cat)
            <a href="{{ route('catalog.category', [$manufacturer['slug'], $cat['slug']]) }}" class="pub-card">
                <div class="pub-card-media">
                    @php
                        $hero = public_path('catalog/'.$manufacturer['slug'].'/'.$cat['slug'].'/hero.webp');
                        $heroUrl = file_exists($hero) ? asset('catalog/'.$manufacturer['slug'].'/'.$cat['slug'].'/hero.webp') : null;
                    @endphp
                    @if($heroUrl)
                        <img src="{{ $heroUrl }}" alt="{{ $cat['name'] }}">
                    @else
                        <div class="pub-card-media--placeholder">{{ $cat['name'] }}</div>
                    @endif
                </div>
                <div class="pub-card-body">
                    <h2 class="pub-card-title">{{ $cat['name'] }}</h2>
                    @if(!empty($cat['description']))
                        <p class="pub-card-text" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">{{ $cat['description'] }}</p>
                    @endif
                    <span class="pub-card-link">
                        Renkleri gör
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endsection
