@extends('layouts.app')
@section('title', 'Yeni Personel')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('personnel.index') }}" class="hover:text-slate-700">Personel</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Personel</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Yeni Personel</h1>
    <p class="text-slate-600 mt-1">Yeni personel bilgilerini girin</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('personnel.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Personel adı soyadı">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-input">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Unvan</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-input" placeholder="Örn: Satış Temsilcisi">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Kategori</label>
                <input type="text" name="category" value="{{ old('category') }}" class="form-input" placeholder="Örn: Satış, Teknik">
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('personnel.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
