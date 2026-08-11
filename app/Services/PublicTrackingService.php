<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\SaleProductionStage;
use App\Models\ServiceTicket;
use App\Support\SaleDelivery;
use App\Support\ServiceTicketStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PublicTrackingService
{
    /** @return array{type: string, code: string, sale?: Sale, ticket?: ServiceTicket}|null */
    public function findByCode(string $code): ?array
    {
        $code = $this->normalizeCode($code);
        if ($code === '') {
            return null;
        }

        $sale = Sale::query()
            ->whereRaw('UPPER(saleNumber) = ?', [mb_strtoupper($code)])
            ->with([
                'customer:id,name',
                'items.product:id,name',
                'payments' => fn ($q) => $q->orderByDesc('paymentDate'),
                'activities',
                'productionStages.saleItem',
                'serviceTickets' => fn ($q) => $q->orderByDesc('openedAt'),
            ])
            ->first();

        if ($sale) {
            return [
                'type' => 'sale',
                'code' => $sale->saleNumber,
                'sale' => $sale,
            ];
        }

        $ticket = ServiceTicket::query()
            ->whereRaw('UPPER(ticketNumber) = ?', [mb_strtoupper($code)])
            ->with([
                'customer:id,name',
                'sale' => fn ($q) => $q->select([
                    'id', 'saleNumber', 'saleDate', 'dueDate', 'orderStatus', 'deliveredAt',
                    'workshopCompletedAt', 'needsFinalMeasurement', 'isCancelled',
                    'grandTotal', 'paidAmount',
                ]),
                'sale.items.product:id,name',
                'shippingCompany:id,name',
                'details' => fn ($q) => $q->orderByDesc('actionDate'),
                'workshopFinishedDetail',
            ])
            ->first();

        if ($ticket) {
            return [
                'type' => 'ssh',
                'code' => $ticket->ticketNumber,
                'ticket' => $ticket,
                'sale' => $ticket->sale,
            ];
        }

        return null;
    }

    public function normalizeCode(string $code): string
    {
        $code = trim(preg_replace('/\s+/u', '', $code) ?? '');
        $code = str_replace(['–', '—'], '-', $code);

        return mb_strtoupper($code);
    }

    /** @return array<string, mixed> */
    public function buildSalePayload(Sale $sale): array
    {
        $status = SaleDelivery::currentStatus($sale);
        $isFinalMeasurement = (bool) ($sale->needsFinalMeasurement ?? false)
            && $status === SaleDelivery::PENDING
            && ! ($sale->isCancelled ?? false);

        $statusKey = $sale->isCancelled
            ? 'cancelled'
            : ($isFinalMeasurement ? SaleDelivery::FINAL_MEASUREMENT : $status);

        $statusLabel = match (true) {
            (bool) ($sale->isCancelled ?? false) => 'İptal edildi',
            $isFinalMeasurement => 'Ölçü bekliyor',
            default => SaleDelivery::label($status),
        };

        $grandTotal = (float) ($sale->grandTotal ?? 0);
        $paidAmount = (float) ($sale->paidAmount ?? 0);
        $remaining = round($grandTotal - $paidAmount, 2);

        $termin = SaleDelivery::terminListMeta($sale);
        $dueHint = null;
        if (! SaleDelivery::isDelivered($sale) && $sale->dueDate && ! ($sale->isCancelled ?? false)) {
            $dueHint = $termin['suffix'] ?? null;
        }

        $paymentTypes = \App\Support\PaymentType::labels();

        $items = ($sale->items ?? collect())->map(function ($item) {
            $name = trim((string) ($item->productName ?: $item->product?->name ?: 'Ürün'));
            $desc = trim((string) ($item->description ?? ''));

            return [
                'name' => $name,
                'description' => $desc !== '' && $desc !== $name ? $desc : null,
                'quantity' => (int) ($item->quantity ?? 0),
                'unitPrice' => (float) ($item->unitPrice ?? 0),
                'lineTotal' => (float) ($item->lineTotal ?? 0),
            ];
        })->values()->all();

        $payments = ($sale->payments ?? collect())->map(fn ($p) => [
            'amount' => (float) ($p->amount ?? 0),
            'date' => $p->paymentDate?->format('d.m.Y'),
            'type' => $paymentTypes[$p->paymentType ?? ''] ?? null,
        ])->values()->all();

        return [
            'type' => 'sale',
            'code' => $sale->saleNumber,
            'customerName' => $this->maskCustomerName($sale->customer?->name),
            'saleDate' => $sale->saleDate?->format('d.m.Y'),
            'dueDate' => $sale->dueDate?->format('d.m.Y'),
            'dueHint' => $dueHint,
            'deliveredAt' => $sale->deliveredAt?->format('d.m.Y'),
            'isCancelled' => (bool) ($sale->isCancelled ?? false),
            'needsFinalMeasurement' => (bool) ($sale->needsFinalMeasurement ?? false),
            'currentStage' => [
                'key' => $statusKey,
                'label' => $statusLabel,
            ],
            'totals' => [
                'grandTotal' => $grandTotal,
                'paidAmount' => $paidAmount,
                'remaining' => $remaining,
                'grandTotalLabel' => \App\Support\Money::format($grandTotal) . ' ₺',
                'paidAmountLabel' => \App\Support\Money::format($paidAmount) . ' ₺',
                'remainingLabel' => \App\Support\Money::format(abs($remaining)) . ' ₺',
                'isPaid' => $remaining <= 0.005,
                'hasDebt' => $remaining > 0.005,
            ],
            'items' => $items,
            'payments' => $payments,
            'stages' => $this->saleStages($sale, $status),
            'history' => $this->saleHistory($sale),
            'serviceTickets' => $sale->serviceTickets->map(fn (ServiceTicket $t) => [
                'ticketNumber' => $t->ticketNumber,
                'status' => $t->status,
                'statusLabel' => ServiceTicketStatus::label($t->status),
                'openedAt' => $t->openedAt?->format('d.m.Y'),
                'closedAt' => $t->closedAt?->format('d.m.Y'),
                'problemSummary' => ServiceTicketStatus::problemSummary($t->reportedProblems),
                'isOpen' => ! ServiceTicketStatus::isClosed($t->status),
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function buildTicketPayload(ServiceTicket $ticket): array
    {
        $status = $ticket->status ?? 'acildi';
        $isClosed = ServiceTicketStatus::isClosed($status);

        $dueHint = null;
        if ($ticket->dueDate && ! $isClosed) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($ticket->dueDate, false);
            if ($daysLeft < 0) {
                $dueHint = abs($daysLeft) . ' gün gecikti';
            } elseif ($daysLeft === 0) {
                $dueHint = 'Servis tarihi bugün';
            } elseif ($daysLeft <= 3) {
                $dueHint = $daysLeft . ' gün kaldı';
            } else {
                $dueHint = $daysLeft . ' gün kaldı';
            }
        }

        $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
        if ($problems === [] && $ticket->issueType) {
            $problems = [['description' => $ticket->issueType, 'status' => 'bekliyor']];
        }
        $fixedCount = collect($problems)->where('status', 'duzeltildi')->count();
        $problemTotal = count($problems);

        $salePayload = null;
        if ($ticket->sale) {
            $sale = $ticket->sale;
            $saleStatus = SaleDelivery::currentStatus($sale);
            $saleItems = ($sale->items ?? collect())->map(function ($item) {
                $name = trim((string) ($item->productName ?: $item->product?->name ?: 'Ürün'));
                $desc = trim((string) ($item->description ?? ''));

                return [
                    'name' => $name,
                    'description' => $desc !== '' && $desc !== $name ? $desc : null,
                    'quantity' => (int) ($item->quantity ?? 0),
                    'lineTotal' => (float) ($item->lineTotal ?? 0),
                ];
            })->values()->all();

            $salePayload = [
                'saleNumber' => $sale->saleNumber,
                'saleDate' => $sale->saleDate?->format('d.m.Y'),
                'dueDate' => $sale->dueDate?->format('d.m.Y'),
                'deliveredAt' => $sale->deliveredAt?->format('d.m.Y'),
                'currentStage' => [
                    'key' => $saleStatus,
                    'label' => SaleDelivery::label($saleStatus),
                ],
                'items' => $saleItems,
            ];
        }

        $hasShipping = $ticket->assignedDriverName
            || $ticket->assignedVehiclePlate
            || $ticket->assignedDriverPhone
            || $ticket->shippingCompany;

        $images = collect(is_array($ticket->images ?? null) ? $ticket->images : [])
            ->filter()
            ->map(fn ($path) => storage_url($path))
            ->filter()
            ->values()
            ->all();

        $serviceCharge = (float) ($ticket->serviceChargeAmount ?? 0);

        return [
            'type' => 'ssh',
            'code' => $ticket->ticketNumber,
            'customerName' => $this->maskCustomerName($ticket->customer?->name),
            'openedAt' => $ticket->openedAt?->format('d.m.Y'),
            'openedAtFull' => $ticket->openedAt?->format('d.m.Y H:i'),
            'dueDate' => $ticket->dueDate?->format('d.m.Y'),
            'dueHint' => $dueHint,
            'closedAt' => $ticket->closedAt?->format('d.m.Y'),
            'closedAtFull' => $ticket->closedAt?->format('d.m.Y H:i'),
            'underWarranty' => (bool) ($ticket->underWarranty ?? false),
            'description' => filled($ticket->description) ? trim((string) $ticket->description) : null,
            'workshopFinished' => $ticket->isWorkshopFinished(),
            'workshopFinishedAt' => $ticket->workshopFinishedDetail?->actionDate?->format('d.m.Y H:i'),
            'currentStage' => [
                'key' => $status,
                'label' => ServiceTicketStatus::label($status),
            ],
            'problemSummary' => ServiceTicketStatus::problemSummary($problems),
            'problemProgress' => [
                'fixed' => $fixedCount,
                'total' => $problemTotal,
                'percent' => $problemTotal > 0 ? (int) round($fixedCount / $problemTotal * 100) : 0,
            ],
            'problems' => collect($problems)->map(fn (array $p) => [
                'description' => $p['description'],
                'status' => $p['status'],
                'statusLabel' => ServiceTicketStatus::problemLabel($p['status']),
            ])->values()->all(),
            'serviceCharge' => $serviceCharge > 0 ? [
                'amount' => $serviceCharge,
                'label' => \App\Support\Money::format($serviceCharge) . ' ₺',
            ] : null,
            'shipping' => $hasShipping ? [
                'company' => $ticket->shippingCompany?->name,
                'driver' => $ticket->assignedDriverName ?: null,
                'phone' => $ticket->assignedDriverPhone ?: null,
                'plate' => $ticket->assignedVehiclePlate ?: null,
            ] : null,
            'images' => $images,
            'stages' => $this->ticketStages($ticket),
            'history' => $this->ticketHistory($ticket),
            'linkedSale' => $salePayload,
        ];
    }

    /**
     * @return list<array{key: string, label: string, done: bool, current: bool, at: ?string}>
     */
    private function saleStages(Sale $sale, string $status): array
    {
        if ($sale->isCancelled ?? false) {
            return [
                ['key' => 'created', 'label' => 'Sipariş alındı', 'done' => true, 'current' => false, 'at' => $sale->saleDate?->format('d.m.Y')],
                ['key' => 'cancelled', 'label' => 'İptal edildi', 'done' => true, 'current' => true, 'at' => null],
            ];
        }

        $order = [
            SaleDelivery::PENDING,
            SaleDelivery::IN_DISCUSSION,
            SaleDelivery::IN_PRODUCTION,
            SaleDelivery::PARTIALLY_DELIVERED,
            SaleDelivery::DELIVERED,
        ];

        $statusIndex = array_search($status, $order, true);
        if ($status === SaleDelivery::SSH) {
            $statusIndex = array_search(SaleDelivery::DELIVERED, $order, true);
        }
        if ($statusIndex === false) {
            $statusIndex = 0;
        }

        $activityDates = $this->statusChangeDates($sale);

        $stages = [];
        $stages[] = [
            'key' => 'created',
            'label' => 'Sipariş alındı',
            'done' => true,
            'current' => false,
            'at' => $sale->saleDate?->format('d.m.Y'),
        ];

        if ($sale->needsFinalMeasurement ?? false) {
            $stages[] = [
                'key' => 'final_measurement',
                'label' => 'Ölçü bekliyor',
                'done' => $status !== SaleDelivery::PENDING,
                'current' => $status === SaleDelivery::PENDING,
                'at' => null,
            ];
        }

        foreach ($order as $i => $key) {
            if ($key === SaleDelivery::PENDING) {
                continue;
            }

            $done = $i <= $statusIndex;
            $current = $status === $key;
            $at = null;

            if ($key === SaleDelivery::DELIVERED && $sale->deliveredAt) {
                $at = $sale->deliveredAt->format('d.m.Y');
                $done = true;
            } elseif ($key === SaleDelivery::IN_PRODUCTION && $sale->workshopCompletedAt) {
                $at = $sale->workshopCompletedAt->format('d.m.Y');
            } elseif (isset($activityDates[$key])) {
                $at = $activityDates[$key];
            }

            if ($key === SaleDelivery::PARTIALLY_DELIVERED && $status !== SaleDelivery::PARTIALLY_DELIVERED && $status !== SaleDelivery::SSH) {
                // Skip optional partial stage unless it was used
                if (! isset($activityDates[$key])) {
                    continue;
                }
            }

            $stages[] = [
                'key' => $key,
                'label' => SaleDelivery::label($key),
                'done' => $done,
                'current' => $current,
                'at' => $at,
            ];
        }

        if ($sale->workshopCompletedAt && $status !== SaleDelivery::DELIVERED) {
            $stages[] = [
                'key' => 'workshop_completed',
                'label' => 'Atölyeden çıktı',
                'done' => true,
                'current' => false,
                'at' => $sale->workshopCompletedAt->format('d.m.Y'),
            ];
        }

        if ($status === SaleDelivery::SSH || $sale->serviceTickets->contains(fn ($t) => ! ServiceTicketStatus::isClosed($t->status))) {
            $stages[] = [
                'key' => SaleDelivery::SSH,
                'label' => 'SSH / servis',
                'done' => $status === SaleDelivery::SSH || $sale->serviceTickets->isNotEmpty(),
                'current' => $status === SaleDelivery::SSH,
                'at' => $sale->serviceTickets->first()?->openedAt?->format('d.m.Y'),
            ];
        }

        // Ensure exactly one current when possible
        if (! collect($stages)->contains(fn ($s) => $s['current'])) {
            foreach (array_reverse($stages, true) as $i => $stage) {
                if ($stage['done']) {
                    $stages[$i]['current'] = true;
                    break;
                }
            }
        }

        return array_values($stages);
    }

    /**
     * @return list<array{key: string, label: string, done: bool, current: bool, at: ?string}>
     */
    private function ticketStages(ServiceTicket $ticket): array
    {
        $order = ['acildi', 'devam_ediyor', 'parca_bekleniyor', 'sevkiyatci_bekleniyor', 'tamamlandi'];
        if ($ticket->status === 'iptal') {
            return [
                ['key' => 'acildi', 'label' => 'Açıldı', 'done' => true, 'current' => false, 'at' => $ticket->openedAt?->format('d.m.Y')],
                ['key' => 'iptal', 'label' => 'İptal', 'done' => true, 'current' => true, 'at' => $ticket->closedAt?->format('d.m.Y')],
            ];
        }

        $idx = array_search($ticket->status, $order, true);
        if ($idx === false) {
            $idx = 0;
        }

        $stages = [];
        foreach ($order as $i => $key) {
            // Ara durumlar yalnızca ilgiliyse veya o aşamaya gelindiyse gösterilsin
            if (in_array($key, ['parca_bekleniyor', 'sevkiyatci_bekleniyor'], true)
                && $ticket->status !== $key
                && $i > $idx) {
                continue;
            }

            $stages[] = [
                'key' => $key,
                'label' => ServiceTicketStatus::label($key),
                'done' => $i <= $idx,
                'current' => $ticket->status === $key,
                'at' => match ($key) {
                    'acildi' => $ticket->openedAt?->format('d.m.Y'),
                    'tamamlandi' => $ticket->closedAt?->format('d.m.Y'),
                    default => null,
                },
            ];
        }

        return $stages;
    }

    /** @return array<string, string> */
    private function statusChangeDates(Sale $sale): array
    {
        $dates = [];
        foreach ($sale->activities ?? [] as $activity) {
            if ($activity->type !== SaleActivity::TYPE_STATUS_CHANGED) {
                continue;
            }
            $to = $activity->metadata['toStatus'] ?? null;
            if ($to && ! isset($dates[$to])) {
                $dates[$to] = $activity->createdAt?->format('d.m.Y');
            }
        }

        return $dates;
    }

    /** @return list<array{at: string, title: string, detail: ?string, source: string}> */
    private function saleHistory(Sale $sale): array
    {
        $entries = collect();

        foreach ($sale->activities ?? [] as $activity) {
            if (! in_array($activity->type, [
                SaleActivity::TYPE_CREATED,
                SaleActivity::TYPE_STATUS_CHANGED,
                SaleActivity::TYPE_WORKSHOP_COMPLETED,
            ], true)) {
                continue;
            }

            $title = match ($activity->type) {
                SaleActivity::TYPE_CREATED => 'Siparişiniz alındı',
                SaleActivity::TYPE_WORKSHOP_COMPLETED => 'Atölye üretimi tamamlandı — sipariş atölyeden çıktı',
                default => $this->publicStatusDescription($activity),
            };

            $entries->push([
                'sort' => $activity->createdAt,
                'at' => $activity->createdAt?->format('d.m.Y H:i') ?? '—',
                'title' => $title,
                'detail' => null,
                'source' => 'activity',
            ]);
        }

        if (SaleDelivery::isDelivered($sale) && $sale->deliveredAt) {
            $hasDelivery = ($sale->activities ?? collect())->contains(
                fn ($a) => $a->type === SaleActivity::TYPE_STATUS_CHANGED
                    && (($a->metadata['toStatus'] ?? null) === SaleDelivery::DELIVERED)
            );
            if (! $hasDelivery) {
                $entries->push([
                    'sort' => $sale->deliveredAt,
                    'at' => $sale->deliveredAt->format('d.m.Y H:i'),
                    'title' => 'Sipariş teslim edildi',
                    'detail' => null,
                    'source' => 'activity',
                ]);
            }
        }

        foreach ($sale->productionStages ?? [] as $stage) {
            /** @var SaleProductionStage $stage */
            if ($stage->type === SaleProductionStage::TYPE_DEFICIENCY && ! $stage->isCompleted) {
                $title = 'Eksiklik kaydedildi';
            } elseif ($stage->type === SaleProductionStage::TYPE_DEFICIENCY) {
                $title = 'Eksiklik giderildi';
            } elseif ($stage->isCompleted) {
                $title = 'Üretim aşaması tamamlandı';
            } else {
                $title = 'Üretim aşaması eklendi';
            }

            $detailParts = [];
            if ($stage->productLabel()) {
                $detailParts[] = $stage->productLabel();
            }
            if ($stage->notes) {
                $detailParts[] = $stage->notes;
            }

            $entries->push([
                'sort' => $stage->completedAt ?? $stage->actionDate ?? $stage->createdAt,
                'at' => ($stage->completedAt ?? $stage->actionDate ?? $stage->createdAt)?->format('d.m.Y H:i') ?? '—',
                'title' => $title,
                'detail' => $detailParts !== [] ? implode(' — ', $detailParts) : null,
                'source' => 'production',
            ]);
        }

        foreach ($sale->serviceTickets ?? [] as $ticket) {
            $entries->push([
                'sort' => $ticket->openedAt,
                'at' => $ticket->openedAt?->format('d.m.Y H:i') ?? '—',
                'title' => 'SSH kaydı açıldı: ' . $ticket->ticketNumber,
                'detail' => ServiceTicketStatus::label($ticket->status)
                    . ($ticket->reportedProblems ? ' · ' . ServiceTicketStatus::problemSummary($ticket->reportedProblems) : ''),
                'source' => 'ssh',
            ]);
            if ($ticket->closedAt) {
                $entries->push([
                    'sort' => $ticket->closedAt,
                    'at' => $ticket->closedAt->format('d.m.Y H:i'),
                    'title' => 'SSH kaydı kapatıldı: ' . $ticket->ticketNumber,
                    'detail' => ServiceTicketStatus::label($ticket->status),
                    'source' => 'ssh',
                ]);
            }
        }

        return $this->sortHistory($entries);
    }

    /** @return list<array{at: string, title: string, detail: ?string, source: string}> */
    private function ticketHistory(ServiceTicket $ticket): array
    {
        $entries = collect();

        foreach ($ticket->details ?? [] as $detail) {
            $entries->push([
                'sort' => $detail->actionDate,
                'at' => $detail->actionDate?->format('d.m.Y H:i') ?? '—',
                'title' => ServiceTicketStatus::detailActionLabel($detail->action),
                'detail' => $detail->notes ? trim((string) $detail->notes) : null,
                'source' => 'ssh',
            ]);
        }

        if ($entries->isEmpty() && $ticket->openedAt) {
            $entries->push([
                'sort' => $ticket->openedAt,
                'at' => $ticket->openedAt->format('d.m.Y H:i'),
                'title' => 'SSH kaydı açıldı',
                'detail' => null,
                'source' => 'ssh',
            ]);
        }

        return $this->sortHistory($entries);
    }

    private function publicStatusDescription(SaleActivity $activity): string
    {
        $to = $activity->metadata['toStatus'] ?? null;
        if ($to === SaleDelivery::DELIVERED) {
            return 'Siparişiniz teslim edildi';
        }
        if ($to) {
            return 'Sipariş durumu: ' . SaleDelivery::label($to);
        }

        return $activity->description ?: 'Sipariş durumu güncellendi';
    }

    private function maskCustomerName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $masked = [];
        foreach ($parts as $part) {
            $len = mb_strlen($part);
            if ($len <= 1) {
                $masked[] = $part;
            } elseif ($len === 2) {
                $masked[] = mb_substr($part, 0, 1) . '*';
            } else {
                $masked[] = mb_substr($part, 0, 1) . str_repeat('*', min($len - 1, 4));
            }
        }

        return implode(' ', $masked);
    }

    /** @param  Collection<int, array{sort: ?CarbonInterface, at: string, title: string, detail: ?string, source: string}>  $entries */
    private function sortHistory(Collection $entries): array
    {
        return $entries
            ->sortByDesc(fn ($e) => $e['sort']?->timestamp ?? 0)
            ->values()
            ->map(fn ($e) => [
                'at' => $e['at'],
                'title' => $e['title'],
                'detail' => $e['detail'],
                'source' => $e['source'],
            ])
            ->all();
    }
}
