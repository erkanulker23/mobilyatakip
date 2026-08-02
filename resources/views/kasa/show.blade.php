@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::kasa($kasa))
@section('content')
@php
    use App\Support\KasaMovement;
    $summary = $summary ?? ['opening' => 0, 'totalIn' => 0, 'totalOut' => 0, 'current' => 0, 'count' => 0];
@endphp

@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="mb-6" x-data="{ showVirman: @json($errors->has('toKasaId') || $errors->has('amount') || $errors->has('movementDate') || $errors->has('description')) }">
    <div class="flex items-start justify-between flex-wrap gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('kasa.index') }}" class="hover:text-neutral-900">Kasa</a>
                <span>/</span>
                <span class="text-neutral-700">{{ $kasa->name }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="page-title">{{ $kasa->name }}</h1>
                <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg {{ $kasa->type === 'banka' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $kasa->type === 'banka' ? 'Banka' : 'Kasa' }}
                </span>
                @if(!($kasa->isActive ?? true))
                <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg bg-neutral-100 text-neutral-600">Pasif</span>
                @endif
            </div>
            @if($kasa->bankName)
            <p class="page-desc mt-1">{{ $kasa->bankName }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(($otherKasalar ?? collect())->isNotEmpty())
            <button type="button" @click="showVirman = true" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                Virman Yap
            </button>
            @endif
            @include('partials.action-buttons', [
                'edit' => route('kasa.edit', $kasa),
                'destroy' => route('kasa.destroy', $kasa),
            ])
        </div>
    </div>

    {{-- Özet kartları --}}
    @php $hasOpening = abs($summary['opening'] ?? 0) >= 0.005; @endphp
    @if($hasOpening && ($summary['count'] ?? 0) === 0)
    <div class="mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p>Güncel bakiye yalnızca <strong>açılış bakiyesi</strong> alanından geliyor; hareket kaydı yok. Başlangıç tutarı tanımlamadıysanız sıfırlayabilirsiniz.</p>
        <form method="POST" action="{{ route('kasa.reset-opening', $kasa) }}" class="shrink-0" onsubmit="return confirm('Açılış bakiyesini sıfırlamak istediğinize emin misiniz?');">
            @csrf
            <button type="submit" class="btn-secondary text-sm whitespace-nowrap">Açılış bakiyesini sıfırla</button>
        </form>
    </div>
    @endif
    <div class="grid grid-cols-2 {{ $hasOpening ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4 mb-6">
        @if($hasOpening)
        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Açılış bakiyesi</p>
                <a href="{{ route('kasa.edit', $kasa) }}" class="text-xs text-sky-600 hover:underline shrink-0">Düzenle</a>
            </div>
            <p class="mt-1 text-xl font-bold tabular-nums {{ $summary['opening'] >= 0 ? 'text-neutral-900' : 'text-red-600' }}">
                {{ number_format($summary['opening'], 0, ',', '.') }} ₺
            </p>
            <p class="mt-1 text-xs text-neutral-500">Kasa oluşturulurken girilen başlangıç tutarı</p>
        </div>
        @endif
        <div class="card p-4 border-emerald-100 bg-emerald-50/40">
            <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Toplam giriş</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-700">+{{ number_format($summary['totalIn'], 0, ',', '.') }} ₺</p>
        </div>
        <div class="card p-4 border-rose-100 bg-rose-50/40">
            <p class="text-xs font-medium text-rose-700 uppercase tracking-wide">Toplam çıkış</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-rose-700">−{{ number_format($summary['totalOut'], 0, ',', '.') }} ₺</p>
        </div>
        <div class="card p-4 border-neutral-200 bg-neutral-900 text-white">
            <p class="text-xs font-medium text-neutral-300 uppercase tracking-wide">Güncel bakiye</p>
            <p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($summary['current'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-400 mt-1">{{ $summary['count'] }} hareket</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        {{-- Hesap bilgileri --}}
        <div class="xl:col-span-1">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-neutral-900 mb-4">Hesap bilgileri</h2>
                <dl class="space-y-3 text-sm">
                    @if($kasa->iban)
                    <div>
                        <dt class="text-neutral-500">IBAN</dt>
                        <dd class="mt-0.5 break-all tabular-nums tracking-wide font-medium">{{ $kasa->iban }}</dd>
                    </div>
                    @endif
                    @if($kasa->accountNumber)
                    <div>
                        <dt class="text-neutral-500">Hesap no</dt>
                        <dd class="mt-0.5 tabular-nums tracking-wide font-medium">{{ $kasa->accountNumber }}</dd>
                    </div>
                    @endif
                    @if($kasa->currency && $kasa->currency !== 'TRY')
                    <div>
                        <dt class="text-neutral-500">Para birimi</dt>
                        <dd class="mt-0.5 font-medium">{{ $kasa->currency }}</dd>
                    </div>
                    @endif
                    @if(!$kasa->iban && !$kasa->accountNumber)
                    <p class="text-neutral-500 text-sm">Ek hesap bilgisi tanımlı değil.</p>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Hareketler --}}
        <div class="xl:col-span-3">
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Hareketler</h2>
                        <p class="text-sm text-neutral-500 mt-0.5">Tahsilat, ödeme, gider ve virman kayıtları</p>
                    </div>
                    <span class="text-sm text-neutral-500">{{ $hareketler->total() }} kayıt</span>
                </div>

                <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/60">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                        <div>
                            <label for="kasa-filter-movement" class="form-label text-xs">İşlem türü</label>
                            <select id="kasa-filter-movement" name="movement" class="form-select text-sm">
                                <option value="">Tümü</option>
                                <option value="tahsilat" {{ request('movement') === 'tahsilat' ? 'selected' : '' }}>Tahsilat</option>
                                <option value="odeme" {{ request('movement') === 'odeme' ? 'selected' : '' }}>Tedarikçi ödemesi</option>
                                <option value="gider" {{ request('movement') === 'gider' ? 'selected' : '' }}>Gider</option>
                                <option value="virman" {{ request('movement') === 'virman' ? 'selected' : '' }}>Virman</option>
                            </select>
                        </div>
                        <div>
                            <label for="kasa-filter-cari" class="form-label text-xs">Cari ara</label>
                            <input id="kasa-filter-cari" type="text" name="cari" value="{{ request('cari') }}" placeholder="Müşteri / tedarikçi" class="form-input text-sm">
                        </div>
                        <div>
                            <label for="kasa-filter-date_from" class="form-label text-xs">Başlangıç</label>
                            <input id="kasa-filter-date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
                        </div>
                        <div>
                            <label for="kasa-filter-date_to" class="form-label text-xs">Bitiş</label>
                            <input id="kasa-filter-date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary text-sm flex-1">Filtrele</button>
                            <a href="{{ route('kasa.show', $kasa) }}" class="btn-secondary text-sm">Temizle</a>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-neutral-100">
                                <th class="table-th">Tarih</th>
                                <th class="table-th">İşlem</th>
                                <th class="table-th">Detay</th>
                                <th class="table-th">Açıklama</th>
                                <th class="table-th text-right">Tutar</th>
                                <th class="table-th text-right w-16">Sil</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($hareketler as $h)
                            @php
                                $badge = KasaMovement::typeBadge($h);
                                $transferDetail = KasaMovement::transferDetail($h, $kasa->id);
                                $cariName = null;
                                $cariUrl = null;
                                $paymentTypeLabel = null;
                                $paymentTypeValue = null;
                                $refId = $h->refId !== null && $h->refId !== '' ? (is_numeric($h->refId) ? (int) $h->refId : $h->refId) : null;

                                if ($h->refType === 'customer_payment' && $refId !== null && isset($customerPayments[$refId])) {
                                    $cp = $customerPayments[$refId];
                                    $cariName = $cp->customer?->name ?? 'Müşteri';
                                    $cariUrl = $cp->customer ? route('customers.show', $cp->customer) : null;
                                    $paymentTypeValue = $cp->paymentType ?? null;
                                    $paymentTypeLabel = $paymentTypes[$paymentTypeValue ?? ''] ?? $paymentTypeValue;
                                } elseif ($h->refType === 'supplier_payment' && $refId !== null && isset($supplierPayments[$refId])) {
                                    $sp = $supplierPayments[$refId];
                                    $cariName = $sp->supplier?->name ?? 'Tedarikçi';
                                    $cariUrl = $sp->supplier ? route('suppliers.show', $sp->supplier) : null;
                                    $paymentTypeValue = $sp->paymentType ?? null;
                                    $paymentTypeLabel = $paymentTypes[$paymentTypeValue ?? ''] ?? $paymentTypeValue;
                                } elseif ($h->refType === 'kasa_transfer') {
                                    $otherKasa = $h->amount < 0 ? $h->toKasa : $h->fromKasa;
                                    if ($otherKasa) {
                                        $cariName = $otherKasa->name;
                                        $cariUrl = route('kasa.show', $otherKasa);
                                    }
                                }

                                $tutar = (float) ($h->amount ?? 0);
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="table-td whitespace-nowrap text-neutral-600">{{ $h->movementDate?->format('d.m.Y') }}</td>
                                <td class="table-td">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium border {{ KasaMovement::toneClasses($badge['tone']) }}">
                                        <span aria-hidden="true">{{ $badge['icon'] }}</span>
                                        {{ $badge['label'] }}
                                    </span>
                                    @if($transferDetail)
                                    <span class="block text-xs text-neutral-500 mt-1">{{ $transferDetail }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    @if($cariUrl)
                                    <a href="{{ $cariUrl }}" class="text-sky-600 hover:underline font-medium">{{ $cariName }}</a>
                                    @elseif($cariName)
                                    <span class="font-medium text-neutral-800">{{ $cariName }}</span>
                                    @else
                                    <span class="text-neutral-400">—</span>
                                    @endif
                                    @if($paymentTypeLabel)
                                    <span class="block text-xs text-neutral-500 mt-0.5">{{ $paymentTypeLabel }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-neutral-600 max-w-xs truncate" title="{{ $h->description }}">{{ $h->description ?: '—' }}</td>
                                <td class="table-td text-right whitespace-nowrap font-semibold tabular-nums {{ $tutar >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $tutar >= 0 ? '+' : '' }}{{ number_format($tutar, 0, ',', '.') }} ₺
                                </td>
                                <td class="table-td text-right">
                                    @php
                                        $deleteConfirm = $h->refType === 'kasa_transfer'
                                            ? 'Bu virman kaydının her iki tarafı da silinecek. Devam edilsin mi?'
                                            : 'Bu hareketi silmek istediğinize emin misiniz? Bağlı tahsilat/ödeme/gider kaydı da kaldırılır.';
                                    @endphp
                                    <form method="POST" action="{{ route('kasa.hareketler.destroy', [$kasa, $h]) }}" class="inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from($deleteConfirm) }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn-delete p-2 rounded-xl transition-colors" title="Sil" aria-label="Hareketi sil">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-medium">Henüz hareket yok</p>
                                    <p class="text-sm mt-1">Tahsilat, ödeme veya virman yapıldığında burada görünür.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($hareketler->hasPages())
                <div class="px-5 py-3 border-t border-neutral-200">{{ $hareketler->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Virman modal --}}
    @if(($otherKasalar ?? collect())->isNotEmpty())
    <div x-show="showVirman" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="virman-title">
        <div class="absolute inset-0 bg-neutral-900/50" @click="showVirman = false"></div>
        <div class="relative w-full max-w-md card p-6 shadow-xl" @click.stop>
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 id="virman-title" class="text-lg font-semibold text-neutral-900">Kasadan kasaya virman</h2>
                    <p class="text-sm text-neutral-500 mt-1">
                        <span class="font-medium text-neutral-700">{{ $kasa->name }}</span> kasasından başka bir kasaya transfer
                    </p>
                </div>
                <button type="button" @click="showVirman = false" class="text-neutral-400 hover:text-neutral-600" aria-label="Kapat">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('kasa.transfer', $kasa) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="virman-toKasaId" class="form-label">Hedef kasa <span class="text-red-500">*</span></label>
                    <select id="virman-toKasaId" name="toKasaId" required class="form-select @error('toKasaId') border-red-500 @enderror">
                        <option value="">Seçin...</option>
                        @foreach($otherKasalar as $ok)
                        <option value="{{ $ok->id }}" {{ old('toKasaId') == $ok->id ? 'selected' : '' }}>
                            {{ $ok->name }}{{ $ok->bankName ? ' — ' . $ok->bankName : '' }} ({{ $ok->type === 'banka' ? 'Banka' : 'Kasa' }})
                        </option>
                        @endforeach
                    </select>
                    @error('toKasaId')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="virman-amount" class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input id="virman-amount" type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}" class="form-input @error('amount') border-red-500 @enderror" placeholder="0">
                    @error('amount')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-neutral-500 mt-1">Güncel bakiye: {{ number_format($summary['current'], 0, ',', '.') }} ₺</p>
                </div>
                <div>
                    <label for="virman-movementDate" class="form-label">Tarih <span class="text-red-500">*</span></label>
                    <input id="virman-movementDate" type="date" name="movementDate" required value="{{ old('movementDate', now()->format('Y-m-d')) }}" class="form-input @error('movementDate') border-red-500 @enderror">
                    @error('movementDate')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="virman-description" class="form-label">Açıklama</label>
                    <input id="virman-description" type="text" name="description" value="{{ old('description') }}" maxlength="500" class="form-input" placeholder="Opsiyonel not">
                    @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showVirman = false" class="btn-secondary flex-1">İptal</button>
                    <button type="submit" class="btn-primary flex-1">Virman yap</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
