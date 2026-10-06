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
        $statusOnlyList = self::isStatusOnlyList($request);
        $applyDateFilter = ! $statusOnlyList;

        $query = Sale::query()
            ->with(['customer', 'personnel', 'branch'])
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

        if ($request->filled('branchId')) {
            if ($request->input('branchId') === 'none') {
                $query->whereNull('branchId');
            } else {
                $query->where('branchId', $request->input('branchId'));
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
            'statusOnlyList' => $statusOnlyList,
        ];
    }

    /** allTime=1 ile gelen operasyon listeleri (raporlar ana sayfa) tarih filtresi kullanmaz. */
    public static function isStatusOnlyList(Request $request): bool
    {
        return $request->boolean('allTime');
    }

    public static function hasExplicitPeriod(Request $request): bool
    {
        if ($request->filled('period') || $request->filled('month')) {
            return true;
        }

        if ($request->filled('from') || $request->filled('to')) {
            return true;
        }

        return $request->filled('year');
    }

    public static function hasExplicitDates(Request $request): bool
    {
        return self::hasExplicitPeriod($request);
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
