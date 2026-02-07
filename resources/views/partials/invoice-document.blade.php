@php $company = \App\Models\Company::first(); @endphp
<div class="card overflow-hidden print:shadow-none print:border-0" id="invoice-document" role="document" aria-label="Fatura belgesi">
    <div class="p-6 md:p-8 lg:p-10">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6 mb-8 pb-6 border-b border-slate-100">
            <div class="flex-1">
                @if($company?->logoUrl)
                <img src="{{ asset($company->logoUrl) }}" alt="Logo" class="h-14 mb-3 object-contain">
                @endif
                <h1 class="text-xl font-bold text-slate-900">{{ $company?->name ?? 'Firma Adı' }}</h1>
                @if($company?->address)<p class="text-sm text-slate-600 mt-1">{{ $company->address }}</p>@endif
                @if($company?->phone)<p class="text-sm text-slate-600">{{ $company->phone }}</p>@endif
                @if($company?->email)<p class="text-sm text-slate-600">{{ $company->email }}</p>@endif
                @if($company?->taxNumber)<p class="text-sm text-slate-600">Vergi No: {{ $company->taxNumber }} @if($company->taxOffice) / {{ $company->taxOffice }} @endif</p>@endif
            </div>
            <div class="md:text-right">
                <h2 class="text-lg font-semibold text-slate-800">{{ $documentTitle }}</h2>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $documentNumber }}</p>
                @if(isset($documentDate) && $documentDate)<p class="text-sm text-slate-600 mt-2">{{ $documentDate->format('d.m.Y') }}</p>@endif
            </div>
        </div>

        {{-- Alıcı / Satıcı Bilgisi --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">{{ $partyLabel ?? 'Alıcı' }}</h3>
                <p class="font-semibold text-slate-900">{{ $partyName ?? '-' }}</p>
                @if(isset($partyAddress) && $partyAddress)<p class="text-sm text-slate-600 mt-1">{{ $partyAddress }}</p>@endif
                @if(isset($partyPhone) && $partyPhone)<p class="text-sm text-slate-600">{{ $partyPhone }}</p>@endif
                @if(isset($partyEmail) && $partyEmail)<p class="text-sm text-slate-600">{{ $partyEmail }}</p>@endif
                @if(isset($partyTax) && $partyTax)<p class="text-sm text-slate-600">Vergi: {{ $partyTax }}</p>@endif
            </div>
            @if(isset($extraInfo) && $extraInfo)
            <div class="md:text-right">
                {!! $extraInfo !!}
            </div>
            @endif
        </div>

        {{-- Kalemler Tablosu --}}
        <div class="overflow-x-auto -mx-2">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün / Açıklama</th>
                        @if(isset($showListPrice) && $showListPrice)
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Liste fiyatı</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">İskontolu fiyat</th>
                        @else
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Birim Fiyat</th>
                        @endif
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Adet</th>
                        @if(isset($showKdv) && $showKdv)
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">KDV %</th>
                        @endif
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($items as $i => $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item['name'] ?? '-' }}</td>
                        @if(isset($showListPrice) && $showListPrice)
                        <td class="px-4 py-3 text-right text-slate-600">{{ isset($item['listPrice']) && $item['listPrice'] !== null ? number_format($item['listPrice'], 2, ',', '.') . ' ₺' : '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item['unitPrice'] ?? 0, 2, ',', '.') }} ₺</td>
                        @else
                        <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item['unitPrice'] ?? 0, 2, ',', '.') }} ₺</td>
                        @endif
                        <td class="px-4 py-3 text-center text-slate-600">{{ $item['quantity'] ?? 0 }}</td>
                        @if(isset($showKdv) && $showKdv)
                        <td class="px-4 py-3 text-right text-slate-600">%{{ number_format($item['kdvRate'] ?? 0, 0) }}</td>
                        @endif
                        <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item['lineTotal'] ?? 0, 2, ',', '.') }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Toplamlar --}}
        <div class="mt-6 flex flex-col items-end">
            @if(isset($subtotal))
            <div class="flex justify-end gap-8 text-sm">
                <span class="text-slate-600">Ara Toplam:</span>
                <span class="font-medium w-32 text-right">{{ number_format($subtotal ?? 0, 2, ',', '.') }} ₺</span>
            </div>
            @endif
            @if(isset($kdvTotal))
            <div class="flex justify-end gap-8 text-sm mt-1">
                <span class="text-slate-600">KDV Toplam:</span>
                <span class="font-medium w-32 text-right">{{ number_format($kdvTotal ?? 0, 2, ',', '.') }} ₺</span>
            </div>
            @endif
            @if(isset($discount) && ($discount ?? 0) > 0)
            <div class="flex justify-end gap-8 text-sm mt-1">
                <span class="text-slate-600">İndirim:</span>
                <span class="font-medium w-32 text-right text-red-600">-{{ number_format($discount ?? 0, 2, ',', '.') }} ₺</span>
            </div>
            @endif
            <div class="flex justify-end gap-8 text-base font-bold mt-3 pt-3 border-t-2 border-slate-200">
                <span class="text-slate-900">Genel Toplam:</span>
                <span class="text-emerald-600 w-32 text-right">{{ number_format($grandTotal ?? 0, 2, ',', '.') }} ₺</span>
            </div>
            @if(isset($paidAmount) && ($paidAmount ?? 0) > 0)
            <div class="flex justify-end gap-8 text-sm mt-2">
                <span class="text-emerald-600">Ödenen:</span>
                <span class="font-medium w-32 text-right text-emerald-600">{{ number_format($paidAmount ?? 0, 2, ',', '.') }} ₺</span>
            </div>
            <div class="flex justify-end gap-8 text-sm mt-1">
                <span class="text-slate-600">Kalan:</span>
                <span class="font-medium w-32 text-right {{ (($grandTotal ?? 0) - ($paidAmount ?? 0)) > 0 ? 'text-red-600' : 'text-slate-600' }}">{{ number_format(($grandTotal ?? 0) - ($paidAmount ?? 0), 2, ',', '.') }} ₺</span>
            </div>
            @endif
        </div>

        @if(isset($notes) && $notes)
        <div class="mt-8 pt-6 border-t border-slate-200">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Notlar</h3>
            <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ $notes }}</p>
        </div>
        @endif
    </div>
</div>
