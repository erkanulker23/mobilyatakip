@extends('layouts.app')
@section('title', 'Yapılacak Listesi')
@section('content')
<div class="mb-6">
    <h1 class="page-title">{{ $showPersonalTasks ?? false ? 'Yapılacaklarım' : 'Yapılacak Listesi' }}</h1>
    <p class="page-desc">{{ ($showPersonalTasks ?? false) ? 'Size atanmış görevlerinizi takvimde görüntüleyin ve tamamlayın' : 'Takvimde tüm görevleri görün; altta her personelin açık görevleri ayrı satırda listelenir' }}</p>
</div>

@include('partials.dashboard-tasks', [
    'taskPersonnel' => $taskPersonnel ?? collect(),
    'personalTasksView' => $showPersonalTasks ?? false,
    'personalPersonnelId' => $personalPersonnelId ?? null,
])
@endsection
