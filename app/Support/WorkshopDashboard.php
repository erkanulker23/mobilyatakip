<?php

namespace App\Support;

use App\Models\Personnel;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\UserTask;
use Carbon\Carbon;

final class WorkshopDashboard
{
    public const TERMIN_HORIZON_DAYS = 14;

    /** @return array<string, mixed> */
    public static function viewData(Personnel $personnel): array
    {
        $terminHorizon = Carbon::today()->addDays(self::TERMIN_HORIZON_DAYS);

        $productionSalesQuery = Sale::query()
            ->with(['customer', 'personnel'])
            ->where('isCancelled', false)
            ->where('orderStatus', SaleDelivery::IN_PRODUCTION)
            ->whereNull('deliveredAt')
            ->orderByRaw('CASE WHEN dueDate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('dueDate');

        SaleProductionStageSchema::applyCounts($productionSalesQuery, detailed: true);

        $productionSales = $productionSalesQuery->get();

        $upcomingDueSales = SaleDelivery::upcomingDueQuery(self::TERMIN_HORIZON_DAYS)
            ->with('customer')
            ->orderBy('dueDate')
            ->get();

        $upcomingInProductionCount = $upcomingDueSales
            ->filter(fn (Sale $s) => SaleDelivery::currentStatus($s) === SaleDelivery::IN_PRODUCTION)
            ->count();

        $openServiceTickets = ServiceTicket::query()
            ->with(['customer', 'sale'])
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->orderByRaw('CASE WHEN dueDate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('dueDate')
            ->orderByDesc('createdAt')
            ->limit(20)
            ->get();

        $workshopStats = (object) [
            'productionCount' => $productionSales->count(),
            'upcomingTerminCount' => $upcomingDueSales->count(),
            'overdueCount' => $upcomingDueSales->filter(fn (Sale $s) => $s->dueDate && $s->dueDate->isPast() && ! $s->dueDate->isToday())->count(),
            'openSshCount' => ServiceTicket::query()->whereNotIn('status', ['tamamlandi', 'iptal'])->count(),
            'openDeficienciesCount' => (int) $productionSales->sum('open_deficiencies_count'),
        ];

        $personnelTasks = collect();
        $taskCompleterFallback = [];
        if ($personnel->hasSystemAccess()) {
            $personnelTasks = UserTask::query()
                ->with('completedByUser:id,name')
                ->where(function ($q) use ($personnel) {
                    $q->where('personnelId', $personnel->id);
                    if ($personnel->userId) {
                        $q->orWhere('userId', $personnel->userId);
                    }
                })
                ->orderBy('isCompleted')
                ->orderByRaw('dueDate IS NULL, dueDate ASC')
                ->orderBy('sortOrder')
                ->orderByDesc('createdAt')
                ->get();
            $taskCompleterFallback = UserTaskCompletion::completerNameMap($personnelTasks);
        }

        $viewingOwnProfile = auth()->user()?->personnel?->id === $personnel->id;

        return [
            'personnel' => $personnel,
            'productionSales' => $productionSales,
            'upcomingDueSales' => $upcomingDueSales,
            'openServiceTickets' => $openServiceTickets,
            'workshopStats' => $workshopStats,
            'viewingOwnProfile' => $viewingOwnProfile,
            'personnelTasks' => $personnelTasks,
            'taskCompleterFallback' => $taskCompleterFallback,
            'productionStagesReady' => SaleProductionStageSchema::isReady(),
            'terminDays' => self::TERMIN_HORIZON_DAYS,
            'upcomingInProductionCount' => $upcomingInProductionCount,
        ];
    }
}
