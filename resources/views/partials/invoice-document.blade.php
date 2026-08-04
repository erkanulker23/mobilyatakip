@php $company = \App\Models\Company::first(); @endphp
<div class="print-document card overflow-hidden print:shadow-none print:border-0" id="invoice-document" role="document" aria-label="Fatura belgesi">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.invoice-brand-header', [
            'documentTitle' => $documentTitle,
            'documentNumber' => $documentNumber,
            'documentDate' => $documentDate ?? null,
            'documentSubtitle' => $documentSubtitle ?? null,
        ])

        @if(!empty($extraInfoRows) && ($showOrderSummary ?? true))
        <dl class="sale-doc-summary print-section mb-5">
            @foreach($extraInfoRows as $row)
            <div class="sale-doc-summary__item">
                <dt class="sale-doc-summary__label">{{ $row['label'] }}</dt>
                <dd class="sale-doc-summary__value">
                    @if(($row['statusKey'] ?? null) && ($row['label'] ?? '') === 'Ödeme Durumu')
                        @include('partials.payment-status-badge', ['status' => ['key' => $row['statusKey'], 'label' => $row['value']]])
                    @else
                        {{ $row['value'] }}
                    @endif
                </dd>
            </div>
            @endforeach
        </dl>
        @endif

        @if(!empty($documentNotice))
        <div class="print-info-banner print-section text-sm text-neutral-800 dark:text-neutral-200 p-3 mb-4">
            {!! $documentNotice !!}
        </div>
        @endif

        <div class="print-section-lg grid grid-cols-1 {{ empty($extraInfoRows) && isset($extraInfo) && $extraInfo ? 'md:grid-cols-2' : '' }} gap-6 mb-4 items-start">
            <div class="min-w-0">
                <h3 class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase mb-2">{{ $partyLabel ?? 'Alıcı' }}</h3>
                <p class="font-semibold text-slate-900 dark:text-neutral-100">{{ $partyName ?? '-' }}</p>
                @if(isset($partyAddress) && $partyAddress)<p class="text-sm text-slate-600 dark:text-neutral-400 mt-0.5 leading-relaxed whitespace-pre-wrap">{{ $partyAddress }}</p>@endif
                @if(isset($partyPhone) && $partyPhone)<p class="text-sm text-slate-600 dark:text-neutral-400">{{ $partyPhone }}</p>@endif
                @if(isset($partyEmail) && $partyEmail)<p class="text-sm text-slate-600 dark:text-neutral-400">{{ $partyEmail }}</p>@endif
                @if(isset($partyTax) && $partyTax)<p class="text-sm text-slate-600 dark:text-neutral-400">Vergi: {{ $partyTax }}</p>@endif
            </div>
            @if(empty($extraInfoRows) && isset($extraInfo) && $extraInfo)
            <div class="md:flex md:justify-end">
                <div class="w-full md:max-w-[17rem]">{!! $extraInfo !!}</div>
            </div>
            @endif
        </div>

        @php
            $kdvIncludedDoc = $kdvIncluded ?? true;
            $displayKdvDetails = isset($kdvIncluded) ? !($kdvIncluded ?? true) : ($showKdv ?? false);
            $displaySubtotal = !$kdvIncludedDoc && isset($subtotal);
        @endphp
        <div class="print-section-lg overflow-x-auto -mx-2">
            <table class="print-table min-w-full">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Ürün / Açıklama</th>
                        @if(isset($showListPrice) && $showListPrice)
                        <th class="text-right">Liste fiyatı</th>
                        <th class="text-right">İskontolu fiyat</th>
                        @else
                        <th class="text-right">Birim Fiyat</th>
                        @endif
                        <th class="text-center">Adet</th>
                        @if($displayKdvDetails)
                        <th class="text-right">KDV %</th>
                        @endif
                        <th class="text-right">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-neutral-700">
                    @foreach($items as $i => $item)
                    <tr class="hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-neutral-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $item['name'] ?? '-' }}
                            @if(!empty($item['description']))
                                @include('partials.item-description-list', [
                                    'description' => $item['description'],
                                    'listClass' => 'item-description-list list-disc list-inside text-xs text-slate-500 dark:text-neutral-400 mt-1 space-y-0.5 pl-0.5',
                                ])
                            @endif
                        </td>
                        @if(isset($showListPrice) && $showListPrice)
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-neutral-400">{{ isset($item['listPrice']) && $item['listPrice'] !== null ? number_format($item['listPrice'], 0, ',', '.') . ' ₺' : '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-neutral-400">{{ number_format($item['unitPrice'] ?? 0, 0, ',', '.') }} ₺</td>
                        @else
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-neutral-400">{{ number_format($item['unitPrice'] ?? 0, 0, ',', '.') }} ₺</td>
                        @endif
                        <td class="px-4 py-3 text-center text-slate-600 dark:text-neutral-400">{{ $item['quantity'] ?? 0 }}</td>
                        @if($displayKdvDetails)
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-neutral-400">%{{ number_format($item['kdvRate'] ?? 0, 0) }}</td>
                        @endif
                        <td class="px-4 py-3 text-right font-medium text-neutral-900 dark:text-neutral-100">{{ number_format($item['lineTotal'] ?? 0, 0, ',', '.') }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="print-section mt-4 flex flex-col items-stretch sm:items-end gap-1">
            @if($displaySubtotal)
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm">
                <span class="text-slate-600 dark:text-neutral-400">Ara Toplam:</span>
                <span class="font-medium sm:w-32 text-right text-neutral-900 dark:text-neutral-100 tabular-nums">{{ number_format($subtotal ?? 0, 0, ',', '.') }} ₺</span>
            </div>
            @endif
            @if($displayKdvDetails && isset($kdvTotal))
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm">
                <span class="text-slate-600 dark:text-neutral-400">KDV Toplam:</span>
                <span class="font-medium sm:w-32 text-right text-neutral-900 dark:text-neutral-100 tabular-nums">{{ number_format($kdvTotal ?? 0, 0, ',', '.') }} ₺</span>
            </div>
            @endif
            @if(isset($discount) && ($discount ?? 0) > 0)
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm">
                <span class="text-slate-600 dark:text-neutral-400">İndirim:</span>
                <span class="font-medium sm:w-32 text-right text-red-600 dark:text-red-400 tabular-nums">-{{ number_format($discount ?? 0, 0, ',', '.') }} ₺</span>
            </div>
            @endif
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-base font-bold mt-2 pt-3 border-t-2 border-neutral-200 dark:border-neutral-700">
                <span class="text-neutral-900 dark:text-neutral-100">Genel Toplam:</span>
                <span class="sm:w-32 text-right text-neutral-900 dark:text-neutral-100 tabular-nums">{{ number_format($grandTotal ?? 0, 0, ',', '.') }} ₺</span>
            </div>
            @if(isset($paidAmount) && ($paidAmount ?? 0) > 0)
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm mt-1">
                <span class="text-slate-600 dark:text-neutral-400">{{ $paidAmountLabel ?? 'Kapora / Ödenen' }}:</span>
                <span class="font-medium sm:w-32 text-right text-neutral-900 dark:text-neutral-100 tabular-nums">{{ number_format($paidAmount ?? 0, 0, ',', '.') }} ₺</span>
            </div>
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm">
                <span class="text-slate-600 dark:text-neutral-400">Kalan:</span>
                <span class="font-medium sm:w-32 text-right tabular-nums {{ (($grandTotal ?? 0) - ($paidAmount ?? 0)) > 0 ? 'text-red-600 dark:text-red-400' : ((($grandTotal ?? 0) - ($paidAmount ?? 0)) < 0 ? 'amount-negative' : 'text-neutral-500 dark:text-neutral-400') }}">{{ number_format(($grandTotal ?? 0) - ($paidAmount ?? 0), 0, ',', '.') }} ₺</span>
            </div>
            @endif
            @if(isset($grandTotal))
            @php $docPaymentStatus = $paymentStatus ?? \App\Support\CustomerBalance::statusFromTotals((float) $grandTotal, (float) ($paidAmount ?? 0)); @endphp
            <div class="flex justify-between sm:justify-end gap-4 sm:gap-8 text-sm mt-1 items-center">
                <span class="text-slate-600 dark:text-neutral-400 shrink-0">Durum:</span>
                <span class="sm:w-32 text-right">@include('partials.payment-status-badge', ['status' => $docPaymentStatus])</span>
            </div>
            @endif
        </div>

        @if(isset($notes) && $notes)
        <div class="print-section pt-4 mt-4 border-t border-neutral-200 dark:border-neutral-700">
            <h3 class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase mb-2">Notlar</h3>
            <p class="text-sm text-slate-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $notes }}</p>
        </div>
        @endif
    </div>
</div>
