@php $company = \App\Models\Company::first(); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0" role="document" aria-label="Sevkiyat gönder fişi">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => $documentTitle ?? 'SEVKİYAT FİŞİ',
                'documentNumber' => $documentNumber,
                'documentDate' => $documentDate ?? null,
                'documentSubtitle' => !empty($dueDate) ? 'Teslim: ' . $dueDate->format('d.m.Y') : null,
            ])

            @if(!empty($documentNotice))
            <div class="print-info-banner print-section">
                {!! $documentNotice !!}
            </div>
            @endif

            <div class="print-meta-grid print-section-lg">
                <div class="print-card">
                    <p class="print-label">{{ $partyLabel ?? 'Teslimat Adresi' }}</p>
                    <p class="print-party-name">{{ $partyName ?? '-' }}</p>
                    @if(!empty($partyAddress))
                    <p class="print-muted mt-1 whitespace-pre-wrap">{{ $partyAddress }}</p>
                    @else
                    <p class="print-muted mt-1">Adres tanımlı değil.</p>
                    @endif
                    @if(!empty($partyPhone))<p class="print-muted mt-1">{{ $partyPhone }}</p>@endif
                    @if(!empty($partyPhone2))<p class="print-muted">{{ $partyPhone2 }}</p>@endif
                    @if(!empty($partyEmail))<p class="print-muted">{{ $partyEmail }}</p>@endif
                </div>
                @if(!empty($personnelName) || !empty($items))
                <div class="print-card print-card--meta">
                    @if(!empty($personnelName))
                    <p class="print-muted">Temsilci: <span class="font-medium">{{ $personnelName }}</span></p>
                    @endif
                    <p class="print-muted mt-1">Kalem: <span class="font-medium">{{ count($items ?? []) }}</span></p>
                </div>
                @endif
            </div>

            <div class="print-section-lg">
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="print-col-no text-left">#</th>
                            <th class="text-left">Ürün / Hizmet</th>
                            <th class="text-left" style="width:18%">Stok Kodu</th>
                            <th class="print-col-qty text-center">Adet</th>
                            @if($showCheckColumn ?? (($slipVariant ?? 'shipment') === 'shipment'))
                            <th class="text-center" style="width:8%">✓</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items ?? [] as $i => $item)
                        <tr>
                            <td class="print-col-no print-muted">{{ $i + 1 }}</td>
                            <td>
                                <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                                @if(!empty($item['description']))
                                @include('partials.item-description-list', ['description' => $item['description']])
                                @endif
                            </td>
                            <td class="print-muted">{{ $item['sku'] ?? '—' }}</td>
                            <td class="text-center font-medium">{{ $item['quantity'] ?? 0 }}</td>
                            @if($showCheckColumn ?? (($slipVariant ?? 'shipment') === 'shipment'))
                            <td class="text-center">
                                <span class="inline-block w-4 h-4 border border-neutral-400"></span>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($showCheckColumn ?? (($slipVariant ?? 'shipment') === 'shipment')) ? 5 : 4 }}" class="text-center print-muted py-4">Sipariş kalemi yok.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(!empty($notes))
            <div class="print-notes-block print-section">
                <p class="print-label">Sipariş Notları</p>
                <p class="whitespace-pre-wrap">{{ $notes }}</p>
            </div>
            @endif

            <div class="print-signatures">
                @if(($slipVariant ?? 'shipment') === 'shipment')
                <p class="text-sm mb-4">
                    Yukarıda listelenen ürün / hizmetleri <strong>eksiksiz</strong> ve <strong>hasarsız</strong> olarak teslim aldığımı beyan ederim.
                </p>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="print-label mb-8">Sevkiyat Görevlisi</p>
                        <div class="sig-line">Ad Soyad / İmza</div>
                        <div class="sig-line mt-10">Tarih</div>
                    </div>
                    <div>
                        <p class="print-label mb-8">Müşteri (Teslim Alan)</p>
                        <div class="sig-line">Ad Soyad / İmza</div>
                        <div class="sig-line mt-10">Tarih</div>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="print-label mb-8">Atölye Sorumlusu</p>
                        <div class="sig-line">Ad Soyad / İmza</div>
                        <div class="sig-line mt-10">Tarih</div>
                    </div>
                    <div>
                        <p class="print-label mb-8">Kontrol / Onay</p>
                        <div class="sig-line">Ad Soyad / İmza</div>
                        <div class="sig-line mt-10">Tarih</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
