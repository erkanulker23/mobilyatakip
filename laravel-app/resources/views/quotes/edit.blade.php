@extends('layouts.app')
@section('title', 'Teklif Düzenle')
@section('content')
<div class="header"><h1>Teklif Düzenle: {{ $quote->quoteNumber }}</h1></div>
<div class="card"><p>Teklif düzenleme burada genişletilebilir.</p><a href="{{ route('quotes.show', $quote) }}" class="btn btn-secondary">Geri</a></div>
@endsection
