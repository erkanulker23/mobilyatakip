<?php

namespace App\Support;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class CustomerLedger
{
    /** @return array<string, mixed> */
    public static function detailDataFromRequest(Customer $customer, Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        return self::detailData($customer, $from, $to);
    }

    /** @return array<string, mixed> */
    public static function detailData(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $customer->load(['sales', 'payments.sale']);

        $rows = collect();
        foreach ($customer->sales()->where('isCancelled', false)->orderBy('saleDate')->orderBy('createdAt')->get() as $s) {
            $rows->push((object) [
                'date' => $s->saleDate,
                'type' => 'satis',
                'ref' => $s->saleNumber,
                'refId' => $s->id,
                'refRoute' => 'sales.show',
                'aciklama' => 'Satış ' . $s->saleNumber,
                'borc' => (float) $s->grandTotal,
                'alacak' => 0,
            ]);
        }
        foreach ($customer->payments()->orderBy('paymentDate')->orderBy('createdAt')->get() as $p) {
            $aciklama = 'Tahsilat';
            if ($p->sale) {
                $aciklama .= ' - ' . $p->sale->saleNumber;
            }
            if ($p->reference) {
                $aciklama .= ' (' . $p->reference . ')';
            }
            $rows->push((object) [
                'date' => $p->paymentDate,
                'type' => 'tahsilat',
                'ref' => null,
                'refId' => $p->id,
                'refRoute' => 'customer-payments.show',
                'aciklama' => $aciklama,
                'borc' => 0,
                'alacak' => (float) $p->amount,
            ]);
        }

        $rows = $rows->sortBy('date')->values();
        $openingBalance = 0;
        $filteredRows = collect();
        foreach ($rows as $r) {
            if ($from && $r->date->lt($from)) {
                $openingBalance += $r->borc - $r->alacak;
                continue;
            }
            if ($to && $r->date->gt($to)) {
                continue;
            }
            $openingBalance += $r->borc - $r->alacak;
            $r->bakiye = $openingBalance;
            $filteredRows->push($r);
        }

        $totalSales = (float) $customer->sales()->where('isCancelled', false)->sum('grandTotal');
        $totalPaid = (float) $customer->payments()->sum('amount');
        $customerBalance = CustomerBalance::customerStatus($totalSales, $totalPaid);

        return compact('customer', 'filteredRows', 'from', 'to', 'openingBalance', 'totalSales', 'totalPaid', 'customerBalance');
    }
}
