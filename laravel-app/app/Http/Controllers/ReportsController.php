<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function incomeExpense(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $gelir = (float) Sale::whereBetween('saleDate', [$from, $to])->sum('grandTotal');
        $tahsilat = (float) CustomerPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');
        $gider = (float) Expense::whereBetween('expenseDate', [$from, $to])->sum('amount');
        $tedarikciOdeme = (float) SupplierPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');

        return view('reports.income-expense', compact('from', 'to', 'gelir', 'tahsilat', 'gider', 'tedarikciOdeme'));
    }

    public function customerLedger(Request $request)
    {
        $customers = Customer::with(['sales', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($c) {
            $borc = (float) $c->sales->sum('grandTotal');
            $alacak = (float) $c->payments->sum('amount');
            $bakiye = $borc - $alacak;
            return (object) [
                'customer' => $c,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $bakiye,
            ];
        });
        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $customers = $customers->filter(fn ($r) => $r->bakiye > 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $customers = $customers->filter(fn ($r) => $r->bakiye < 0)->values();
            }
        }
        return view('reports.customer-ledger', compact('customers'));
    }

    public function supplierLedger(Request $request)
    {
        $suppliers = Supplier::with(['purchases', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($s) {
            $borc = (float) $s->purchases->sum('grandTotal');
            $alacak = (float) $s->payments->sum('amount');
            $bakiye = $borc - $alacak;
            return (object) [
                'supplier' => $s,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $bakiye,
            ];
        });
        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye < 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye > 0)->values();
            }
        }
        return view('reports.supplier-ledger', compact('suppliers'));
    }
}
