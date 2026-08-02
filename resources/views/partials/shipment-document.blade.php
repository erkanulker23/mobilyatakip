@php $company = \App\Models\Company::first(); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0" role="document" aria-label="Sevkiyat gönder fişi">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => $documentTitle ?? 'SEVKİYAT GÖNDER FİŞİ',
            'documentNumber' => $documentNumber,
            'documentDate' => $documentDate ?? null,
            'documentSubtitle' => !empty($dueDate) ? 'Teslim: ' . $dueDate->format('d.m.Y') : null,
        ])

        <div class="print-section-lg grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-2">{{ $partyLabel ?? 'Teslimat Adresi' }}</h3>
                <p class="font-semibold text-slate-900">{{ $partyName ?? '-' }}</p>
                @if(!empty($partyAddress))
                <p class="text-sm text-slate-600 mt-1 whitespace-pre-wrap">{{ $partyAddress }}</p>
                @else
                <p class="text-sm text-amber-700 mt-1">Adres tanımlı değil.</p>
                @endif
                @if(!empty($partyPhone))<p class="text-sm text-slate-600 mt-1">{{ $partyPhone }}</p>@endif
                @if(!empty($partyPhone2))<p class="text-sm text-slate-600">{{ $partyPhone2 }}</p>@endif
                @if(!empty($partyEmail))<p class="text-sm text-slate-600">{{ $partyEmail }}</p>@endif
            </div>
            @if(!empty($personnelName) || !empty($items))
            <div class="md:text-right text-sm text-slate-600 space-y-1">
                @if(!empty($personnelName))
                <p>Temsilci: <span class="font-medium text-slate-900">{{ $personnelName }}</span></p>
                @endif
                <p>Kalem: <span class="font-medium text-slate-900">{{ count($items ?? []) }}</span></p>
            </div>
            @endif
        </div>

        <div class="print-section-lg overflow-x-auto">
            <table class="print-table min-w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Ürün / Hizmet</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase w-28">Stok Kodu</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase w-16">Adet</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase w-12">✓</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items ?? [] as $i => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-neutral-900">{{ $item['name'] ?? '-' }}</p>
                            @if(!empty($item['description']))
                            <p class="text-xs text-neutral-500 whitespace-pre-wrap mt-0.5">{{ $item['description'] }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $item['sku'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-slate-900">{{ $item['quantity'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block w-4 h-4 border border-slate-400 rounded-sm"></span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-neutral-500">Sipariş kalemi yok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($notes))
        <div class="print-section mt-4 pt-3 border-t border-neutral-200">
            <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-1">Sipariş Notları</h3>
            <p class="text-sm text-neutral-700 whitespace-pre-wrap">{{ $notes }}</p>
        </div>
        @endif

        <div class="print-signatures mt-6">
            <p class="text-sm text-neutral-700 mb-4">
                Yukarıda listelenen ürün / hizmetleri <strong>eksiksiz</strong> ve <strong>hasarsız</strong> olarak teslim aldığımı beyan ederim.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p class="text-xs font-semibold text-neutral-500 uppercase mb-8">Sevkiyat Görevlisi</p>
                    <div class="sig-line">Ad Soyad / İmza</div>
                    <div class="sig-line mt-10">Tarih</div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-neutral-500 uppercase mb-8">Müşteri (Teslim Alan)</p>
                    <div class="sig-line">Ad Soyad / İmza</div>
                    <div class="sig-line mt-10">Tarih</div>
                </div>
            </div>
        </div>
    </div>
</div>
