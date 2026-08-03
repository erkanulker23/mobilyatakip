@php $company = \App\Models\Company::first(); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0" id="invoice-document" role="document" aria-label="Fatura belgesi">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => $documentTitle,
                'documentNumber' => $documentNumber,
                'documentDate' => $documentDate ?? null,
                'documentSubtitle' => $documentSubtitle ?? null,
            ])

            @if(!empty($documentNotice))
            <div class="print-info-banner print-section">
                {!! $documentNotice !!}
            </div>
            @endif

            <div class="print-meta-grid print-section-lg">
                <div class="print-card">
                    <p class="print-label">{{ $partyLabel ?? 'Alıcı' }}</p>
                    <p class="print-party-name">{{ $partyName ?? '-' }}</p>
                    @if(isset($partyAddress) && $partyAddress)<p class="print-muted mt-1">{{ $partyAddress }}</p>@endif
                    @if(isset($partyPhone) && $partyPhone)<p class="print-muted mt-1">{{ $partyPhone }}</p>@endif
                    @if(isset($partyEmail) && $partyEmail)<p class="print-muted">{{ $partyEmail }}</p>@endif
                    @if(isset($partyTax) && $partyTax)<p class="print-muted">Vergi: {{ $partyTax }}</p>@endif
                </div>
                @if(isset($extraInfo) && $extraInfo)
                <div class="print-card print-card--meta">
                    {!! $extraInfo !!}
                </div>
                @endif
            </div>

            @php
                $kdvIncludedDoc = $kdvIncluded ?? true;
                $displayKdvDetails = isset($kdvIncluded) ? !($kdvIncluded ?? true) : ($showKdv ?? false);
                $displaySubtotal = !$kdvIncludedDoc && isset($subtotal);
            @endphp
            <div class="print-section-lg print-items-table">
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="text-left print-col-no">#</th>
                            <th class="text-left print-col-name">Ürün / Açıklama</th>
                            @if(isset($showListPrice) && $showListPrice)
                            <th class="text-right">Liste fiyatı</th>
                            <th class="text-right">İskontolu fiyat</th>
                            @else
                            <th class="text-right print-col-price">Birim Fiyat</th>
                            @endif
                            <th class="text-center print-col-qty">Adet</th>
                            @if($displayKdvDetails)
                            <th class="text-right print-col-kdv">KDV %</th>
                            @endif
                            <th class="text-right print-col-total">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        <tr>
                            <td class="print-col-no print-muted">{{ $i + 1 }}</td>
                            <td class="print-col-name">
                                <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                                @if(!empty($item['description']))
                                    @include('partials.item-description-list', ['description' => $item['description']])
                                @endif
                            </td>
                            @if(isset($showListPrice) && $showListPrice)
                            <td class="text-right print-muted">{{ isset($item['listPrice']) && $item['listPrice'] !== null ? number_format($item['listPrice'], 0, ',', '.') . ' ₺' : '—' }}</td>
                            <td class="text-right print-muted">{{ number_format($item['unitPrice'] ?? 0, 0, ',', '.') }} ₺</td>
                            @else
                            <td class="text-right print-col-price print-muted">{{ number_format($item['unitPrice'] ?? 0, 0, ',', '.') }} ₺</td>
                            @endif
                            <td class="text-center print-col-qty">{{ $item['quantity'] ?? 0 }}</td>
                            @if($displayKdvDetails)
                            <td class="text-right print-col-kdv print-muted">%{{ number_format($item['kdvRate'] ?? 0, 0) }}</td>
                            @endif
                            <td class="text-right print-col-total font-medium">{{ number_format($item['lineTotal'] ?? 0, 0, ',', '.') }} ₺</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="print-totals-panel print-totals-block">
                @if($displaySubtotal)
                <div class="print-totals-row">
                    <span>Ara Toplam</span>
                    <span>{{ number_format($subtotal ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                @endif
                @if($displayKdvDetails && isset($kdvTotal))
                <div class="print-totals-row">
                    <span>KDV Toplam</span>
                    <span>{{ number_format($kdvTotal ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                @endif
                @if(isset($discount) && ($discount ?? 0) > 0)
                <div class="print-totals-row">
                    <span>İndirim</span>
                    <span>-{{ number_format($discount ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                @endif
                <div class="print-totals-grand">
                    <span>Genel Toplam</span>
                    <span>{{ number_format($grandTotal ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                @if(isset($paidAmount) && ($paidAmount ?? 0) > 0)
                <div class="print-totals-row print-totals-row--after-grand">
                    <span>{{ $paidAmountLabel ?? 'Kapora / Ödenen' }}</span>
                    <span>{{ number_format($paidAmount ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                <div class="print-totals-row">
                    <span>Kalan</span>
                    <span>{{ number_format(($grandTotal ?? 0) - ($paidAmount ?? 0), 0, ',', '.') }} ₺</span>
                </div>
                @endif
                @if(isset($grandTotal))
                @php $docPaymentStatus = $paymentStatus ?? \App\Support\CustomerBalance::statusFromTotals((float) $grandTotal, (float) ($paidAmount ?? 0)); @endphp
                <div class="print-totals-row print-totals-row--status">
                    <span>Durum</span>
                    <span>@include('partials.payment-status-badge', ['status' => $docPaymentStatus])</span>
                </div>
                @endif
            </div>

            @if(isset($notes) && $notes)
            <div class="print-notes-block print-section">
                <p class="print-label">Notlar</p>
                <p class="whitespace-pre-wrap">{{ $notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
