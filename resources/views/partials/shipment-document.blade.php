@php
    $company = \App\Models\Company::first();
    $itemCount = count($items ?? []);
    $compactTable = $itemCount > 8;
    $showCheck = $showCheckColumn ?? (($slipVariant ?? 'shipment') === 'shipment');
@endphp
<div class="print-document print-document--fit {{ $compactTable ? 'print-document--compact' : '' }} card overflow-hidden print:shadow-none print:border-0" role="document" aria-label="Sevkiyat gönder fişi">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header-sheet', [
                'documentTitle' => $documentTitle ?? 'SEVKİYAT FİŞİ',
                'documentNumber' => $documentNumber,
                'documentDate' => $documentDate ?? null,
                'documentSubtitle' => !empty($dueDate) ? 'Teslim: ' . $dueDate->format('d.m.Y') : ($documentSubtitle ?? null),
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
                    @if(!empty($partyPhone))<p class="print-muted mt-1">Tel: {{ $partyPhone }}</p>@endif
                    @if(!empty($partyPhone2))<p class="print-muted">Tel 2: {{ $partyPhone2 }}</p>@endif
                    @if(!empty($partyEmail))<p class="print-muted">{{ $partyEmail }}</p>@endif
                </div>
                <div class="print-card print-card--meta">
                    <p class="print-label">Fiş Bilgileri</p>
                    <div class="print-kv-list">
                        @if(!empty($personnelName))
                        <div class="print-kv-row"><span class="print-kv-label">Temsilci</span><span class="print-kv-value">{{ $personnelName }}</span></div>
                        @endif
                        <div class="print-kv-row"><span class="print-kv-label">Kalem Sayısı</span><span class="print-kv-value">{{ $itemCount }}</span></div>
                        @if(!empty($dueDate))
                        <div class="print-kv-row"><span class="print-kv-label">Termin</span><span class="print-kv-value">{{ $dueDate->format('d.m.Y') }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="print-section-lg print-items-table">
                <table class="print-table {{ $compactTable ? 'print-table--compact' : '' }}">
                    <thead>
                        <tr>
                            <th class="print-col-no text-left">#</th>
                            <th class="text-left">Ürün / Hizmet</th>
                            <th class="text-left" style="width:16%">Stok Kodu</th>
                            <th class="print-col-qty text-center">Adet</th>
                            @if($showCheck)
                            <th class="text-center" style="width:7%">Teslim</th>
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
                            @if($showCheck)
                            <td class="text-center"><span class="print-check-box" aria-hidden="true"></span></td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $showCheck ? 5 : 4 }}" class="text-center print-muted py-4">Sipariş kalemi yok.</td>
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

            <div class="print-signatures print-signatures--compact">
                @if(($slipVariant ?? 'shipment') === 'shipment')
                <p class="text-sm mb-3">
                    Yukarıda listelenen ürün / hizmetleri <strong>eksiksiz</strong> ve <strong>hasarsız</strong> olarak teslim aldığımı beyan ederim.
                </p>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="print-label">Sevkiyat Görevlisi</p>
                        <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                    </div>
                    <div>
                        <p class="print-label">Müşteri (Teslim Alan)</p>
                        <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="print-label">Atölye Sorumlusu</p>
                        <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                    </div>
                    <div>
                        <p class="print-label">Kontrol / Onay</p>
                        <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                    </div>
                </div>
                @endif
            </div>

            @include('partials.print-document-footer', [
                'documentRef' => ($documentTitle ?? 'Sevkiyat') . ' · ' . ($documentNumber ?? ''),
                'footerNote' => 'Fiyat bilgisi içermez. Sevkiyat / atölye kullanım fişidir.',
            ])
        </div>
    </div>
</div>
