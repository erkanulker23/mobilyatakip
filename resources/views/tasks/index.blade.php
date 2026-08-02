@extends('layouts.app')
@section('title', 'Yapılacak Listesi')
@section('content')
<div class="mb-6">
    <h1 class="page-title">Yapılacak Listesi</h1>
    <p class="page-desc">Görevlerinizi takvimde planlayın, renklendirin ve tamamlayın</p>
</div>

@include('partials.dashboard-tasks')
@endsection
