<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class SalesReportQuery
{
    /** @return array{query: Builder, applyDateFilter: bool, statusOnlyList: bool} */
    public static function fromRequest(Carbon $from, Carbon $to, Request $request): array
    {
        $deliveryStatus = SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            ? $request->input('deliveryStatus')
            : null;
        $odeme = $request->input('odeme');
        $hasStatusFilter = $deliveryStatus !== null || in_array($odeme, ['borclu', 'borcsuz'], true);
        $hasExplicitDates = self::hasExplicitDates($request);
        $statusOnlyList = $hasStatusFilter && ! $hasExplicitDates;

        $query = Sale::query()
            ->with(['customer', 'personnel'])
            ->where('isCancelled', false);

        if (! $statusOnlyList) {
            self::applyDateRange($query, $from, $to);
        }

        if ($request->filled('personnelId')) {
            if ($request->input('personnelId') === 'none') {
                $query->whereNull('personnelId');
            } else {
                $query->where('personnelId', $request->input('personnelId'));
            }
        }

        if ($deliveryStatus) {
            SaleDelivery::applyDeliveryFilter($query, $deliveryStatus);
        }

        if ($odeme === 'borclu') {
            $query->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005');
        } elseif ($odeme === 'borcsuz') {
            $query->whereRaw('ABS(grandTotal - COALESCE(paidAmount, 0)) <= 0.005');
        }

        return [
            'query' => $query,
            'applyDateFilter' => ! $statusOnlyList,
            'statusOnlyList' => $statusOnlyList,
        ];
    }

    public static function hasExplicitDates(Request $request): bool
    {
        if ($request->filled('from') || $request->filled('to')) {
            return true;
        }

        return $request->filled('year');
    }

    /** @param Builder<Sale> $query */
    public static function applyDateRange(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereDate('saleDate', '>=', $from->toDateString())
            ->whereDate('saleDate', '<=', $to->toDateString());
    }
}
