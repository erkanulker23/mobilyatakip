<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private AuditService $auditService
    ) {}

    public function create()
    {
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $products = Product::where('isActive', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('sales.create', compact('customers', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'warehouseId' => 'required|exists:warehouses,id',
            'saleDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'kdvIncluded' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
        ]);
        try {
            $sale = $this->saleService->createDirect([
                'customerId' => $validated['customerId'],
                'warehouseId' => $validated['warehouseId'],
                'saleDate' => $validated['saleDate'],
                'dueDate' => $validated['dueDate'] ?? null,
                'kdvIncluded' => $request->boolean('kdvIncluded'),
                'notes' => $validated['notes'] ?? null,
                'items' => $validated['items'],
            ]);
            $this->auditService->logCreate('sale', $sale->id, ['saleNumber' => $sale->saleNumber, 'grandTotal' => $sale->grandTotal]);
            return redirect()->route('sales.show', $sale)->with('success', 'Satış oluşturuldu.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $q = Sale::with('customer')->orderBy('createdAt', 'desc');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('saleNumber', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('customerId')) {
            $q->where('customerId', $request->customerId);
        }
        if ($request->filled('from')) {
            $q->whereDate('saleDate', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('saleDate', '<=', $request->to);
        }
        $sales = $q->paginate(20)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        return view('sales.index', compact('sales', 'customers'));
    }

    public function show(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) abort(404);
        return view('sales.show', compact('sale'));
    }

    public function print(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) abort(404);
        return view('sales.print', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        $this->auditService->logDelete('sale', $sale->id, ['saleNumber' => $sale->saleNumber, 'grandTotal' => (float) $sale->grandTotal]);
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Satış silindi.');
    }

    public function cancel(Sale $sale)
    {
        $this->auditService->logCancel('sale', $sale->id);
        $sale->update(['isCancelled' => true]);
        return redirect()->route('sales.show', $sale)->with('success', 'Satış iptal edildi.');
    }
}
