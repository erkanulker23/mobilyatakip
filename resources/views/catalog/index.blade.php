@extends('layouts.public-gate-page')

@section('meta')
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Malzeme Kataloğu',
        'metaDescription' => 'Mobilya malzemeleri, renk ve dekor örnekleri. Firmalara göre panel ve yüzey seçeneklerini inceleyin.',
    ])
@endsection

@section('active_portal', 'katalog')
@section('hero_aria', 'Malzeme kataloğu')

@section('hero')
    <p class="hero-kicker">Renk · Dekor · Panel</p>
    <h1 class="hero-title">Malzeme kataloğu</h1>
    <p class="hero-lead">Firmalara göre panel, dekor ve renk örneklerini inceleyin. Seçtiğiniz kodu siparişinizde kullanabilirsiniz.</p>
@endsection

@section('footer_extra')
    <a href="{{ url('/takip') }}">Sipariş takibi</a>
@endsection

@section('content')
    @if(count($manufacturers) === 0)
        <div class="pub-box pub-empty">Henüz katalog eklenmedi.</div>
    @else
        <div class="pub-grid pub-grid--3">
            @foreach($manufacturers as $m)
                <a href="{{ route('catalog.manufacturer', $m['slug']) }}" class="pub-card">
                    <div class="pub-card-body">
                        <div class="pub-card-kicker">Firma</div>
                        <h2 class="pub-card-title">{{ $m['name'] }}</h2>
                        @if(!empty($m['categories']))
                            <p class="pub-card-text">
                                {{ count($m['categories']) }} kategori
                                · {{ collect($m['categories'])->pluck('name')->take(2)->implode(', ') }}{{ count($m['categories']) > 2 ? '…' : '' }}
                            </p>
                        @endif
                        <span class="pub-card-link">
                            İncele
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
