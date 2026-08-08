<div id="customersListResults" class="transition-opacity duration-150">
    <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3 text-sm text-neutral-500">
        <span>
            @if($customers->total() === 0)
                Kayıt bulunamadı
            @elseif($customers->total() === 1)
                1 müşteri
            @else
                {{ number_format($customers->total(), 0, ',', '.') }} müşteri
                @if($customers->hasPages())
                    · sayfa {{ $customers->currentPage() }}/{{ $customers->lastPage() }}
                @endif
            @endif
        </span>
        @if(request()->hasAny(['search', 'balance', 'isActive']))
            <span class="text-xs text-neutral-400">Filtre uygulanıyor</span>
        @endif
    </div>

    <div class="overflow-x-auto -mx-px">
        <table class="min-w-full customers-index-table">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th">Müşteri</th>
                    <th class="table-th col-hide-mobile">Telefon</th>
                    <th class="table-th col-hide-mobile">İl</th>
                    <th class="table-th col-hide-mobile">İlçe</th>
                    <th class="table-th col-hide-mobile text-center">Sipariş</th>
                    <th class="table-th">Cari</th>
                    <th class="table-th col-hide-mobile">Kayıt</th>
                    <th class="table-th text-right w-36 sm:w-44">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                @php
                    $cari = \App\Support\CustomerBalance::customerStatus((float) ($c->totalSales ?? 0), (float) ($c->totalPaid ?? 0));
                    $initial = mb_strtoupper(mb_substr($c->name, 0, 1));
                    $avatarHue = crc32($c->name) % 360;
                    $cityName = $c->city?->name;
                    $districtName = $c->district?->name;
                    $location = trim(collect([$cityName, $districtName])->filter()->implode(' / '));
                @endphp
                <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors {{ !($c->isActive ?? true) ? 'opacity-70' : '' }}">
                    <td class="table-td min-w-[10rem]">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full shrink-0 flex items-center justify-center text-sm font-semibold text-white" style="background-color: hsl({{ $avatarHue }}, 45%, 42%);">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('customers.show', $c) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline truncate block">{{ $c->name }}</a>
                                @if(!($c->isActive ?? true))
                                    <span class="text-xs text-neutral-400">Pasif</span>
                                @endif
                                @if($c->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}" class="block mt-0.5 text-xs text-neutral-500 md:hidden cell-phone">{{ $c->phone }}</a>
                                @endif
                                @if($location)
                                    <span class="block mt-0.5 text-xs text-neutral-400 truncate md:hidden max-w-[14rem]">{{ $location }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="table-td col-hide-mobile cell-phone">
                        @if($c->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}" class="text-neutral-700 dark:text-neutral-300 hover:underline">{{ $c->phone }}</a>
                            @if($c->phone2)
                                <span class="block text-xs text-neutral-400 mt-0.5">{{ $c->phone2 }}</span>
                            @endif
                        @else
                            <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 whitespace-nowrap col-hide-mobile">{{ $cityName ?: '—' }}</td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 whitespace-nowrap col-hide-mobile">{{ $districtName ?: '—' }}</td>
                    <td class="table-td text-center text-neutral-600 dark:text-neutral-400 col-hide-mobile">{{ (int) ($c->ordersCount ?? 0) }}</td>
                    <td class="table-td min-w-[6.5rem]">
                        <div class="flex flex-col items-start gap-1">
                            @include('partials.payment-status-badge', ['status' => ['key' => $cari['key'], 'label' => $cari['label']]])
                            @if($cari['amount'] > 0)
                                <span class="text-xs font-medium tabular-nums {{ $cari['key'] === 'borclu' ? 'text-red-600 dark:text-red-400' : ($cari['key'] === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : 'text-neutral-500') }}">
                                    {{ number_format($cari['amount'], 0, ',', '.') }} ₺
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 whitespace-nowrap col-hide-mobile">{{ $c->createdAt?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('customers.show', $c),
                            'edit' => route('customers.edit', $c),
                            'destroy' => route('customers.destroy', $c),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <p class="text-neutral-500 text-sm">Aramanıza uygun müşteri bulunamadı.</p>
                        @if(request()->hasAny(['search', 'balance', 'isActive']))
                            <a href="{{ route('customers.index') }}" class="btn-secondary mt-4 text-sm">Filtreleri temizle</a>
                        @else
                            <a href="{{ route('customers.create') }}" class="btn-primary mt-4 text-sm">İlk müşteriyi ekle</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100 dark:border-neutral-800">{{ $customers->links() }}</div>
    @endif
</div>
