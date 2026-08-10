<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class SalesReportQuery
{
    /** @return array{query: Builder, applyDateFilter: bool} */
    public static function fromRequest(Carbon $from, Carbon $to, Request $request): array
    {
        $deliveryStatus = SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            ? $request->input('deliveryStatus')
            : null;
        $odeme = $request->input('odeme');
        $applyDateFilter = self::hasExplicitDates($request);

        $query = Sale::query()
            ->with(['customer', 'personnel'])
            ->where('isCancelled', false);

        if ($applyDateFilter) {
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
            'applyDateFilter' => $applyDateFilter,
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
        $query->whereBetween('saleDate', [
            $from->toDateString(),
            $to->toDateString(),
        ]);
    }
}
