<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(private AuditService $auditService) {}
    public function index(Request $request)
    {
        $q = Purchase::with('supplier')->orderBy('createdAt', 'desc');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('purchaseNumber', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('supplierId')) {
            $q->where('supplierId', $request->supplierId);
        }
        if ($request->filled('from')) {
            $q->whereDate('purchaseDate', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('purchaseDate', '<=', $request->to);
        }
        $purchases = $q->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();
        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'purchaseDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'kdvIncluded' => 'nullable|boolean',
            'supplierDiscountRate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.listPrice' => 'nullable|numeric|min:0',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
        ]);
        $kdvIncluded = $request->boolean('kdvIncluded');

        $purchase = DB::transaction(function () use ($validated, $kdvIncluded) {
            $last = Purchase::whereYear('createdAt', date('Y'))
                ->orderBy('purchaseNumber', 'desc')
                ->lockForUpdate()
                ->first();
            $next = $last ? (int) preg_replace('/^ALS-\d+-/', '', $last->purchaseNumber) + 1 : 1;
            $purchaseNumber = 'ALS-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

            $purchase = Purchase::create([
                'purchaseNumber' => $purchaseNumber,
                'supplierId' => $validated['supplierId'],
                'purchaseDate' => $validated['purchaseDate'],
                'dueDate' => $validated['dueDate'] ?? null,
                'kdvIncluded' => $kdvIncluded,
                'supplierDiscountRate' => isset($validated['supplierDiscountRate']) ? (float) $validated['supplierDiscountRate'] : null,
                'subtotal' => 0,
                'kdvTotal' => 0,
                'grandTotal' => 0,
                'paidAmount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);
            $subtotal = 0;
            $kdvTotal = 0;
            foreach ($validated['items'] as $row) {
                $unitPrice = (float) $row['unitPrice'];
                $listPrice = isset($row['listPrice']) && $row['listPrice'] !== '' ? (float) $row['listPrice'] : null;
                $qty = (int) $row['quantity'];
                $kdvRate = (float) ($row['kdvRate'] ?? 18);
                if ($kdvIncluded) {
                    $lineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                    $lineKdv = round($unitPrice * $qty - $lineNet, 2);
                    $lineTotal = round($unitPrice * $qty, 2);
                } else {
                    $lineNet = round($unitPrice * $qty, 2);
                    $lineKdv = round($lineNet * ($kdvRate / 100), 2);
                    $lineTotal = round($lineNet + $lineKdv, 2);
                }
                $subtotal += $lineNet;
                $kdvTotal += $lineKdv;
                PurchaseItem::create([
                    'purchaseId' => $purchase->id,
                    'productId' => $row['productId'],
                    'unitPrice' => $unitPrice,
                    'listPrice' => $listPrice,
                    'quantity' => $qty,
                    'kdvRate' => $kdvRate,
                    'lineTotal' => $lineTotal,
                ]);
            }
            $discRate = (float) ($purchase->supplierDiscountRate ?? 0);
            if ($discRate > 0 && $discRate <= 100) {
                $subtotal = round($subtotal * (1 - $discRate / 100), 2);
                $kdvTotal = round($kdvTotal * (1 - $discRate / 100), 2);
            }
            $grandTotal = round($subtotal + $kdvTotal, 2);
            $purchase->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
            return $purchase;
        });

        $this->auditService->logCreate('purchase', $purchase->id, ['purchaseNumber' => $purchase->purchaseNumber, 'grandTotal' => $purchase->grandTotal]);
        return redirect()->route('purchases.show', $purchase)->with('success', 'Alış kaydedildi.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        return view('purchases.show', compact('purchase'));
    }

    public function print(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        return view('purchases.print', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'purchaseDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'kdvIncluded' => 'nullable|boolean',
            'supplierDiscountRate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.listPrice' => 'nullable|numeric|min:0',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
        ]);
        $kdvIncluded = $request->boolean('kdvIncluded');
        $purchase->update([
            'supplierId' => $validated['supplierId'],
            'purchaseDate' => $validated['purchaseDate'],
            'dueDate' => $validated['dueDate'] ?? null,
            'kdvIncluded' => $kdvIncluded,
            'supplierDiscountRate' => array_key_exists('supplierDiscountRate', $validated) && $validated['supplierDiscountRate'] !== '' ? (float) $validated['supplierDiscountRate'] : null,
            'notes' => $validated['notes'] ?? null,
        ]);
        $purchase->items()->delete();
        $subtotal = 0;
        $kdvTotal = 0;
        foreach ($validated['items'] as $row) {
            $unitPrice = (float) $row['unitPrice'];
            $listPrice = isset($row['listPrice']) && $row['listPrice'] !== '' ? (float) $row['listPrice'] : null;
            $qty = (int) $row['quantity'];
            $kdvRate = (float) ($row['kdvRate'] ?? 18);
            if ($kdvIncluded) {
                $lineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                $lineKdv = round($unitPrice * $qty - $lineNet, 2);
                $lineTotal = round($unitPrice * $qty, 2);
            } else {
                $lineNet = round($unitPrice * $qty, 2);
                $lineKdv = round($lineNet * ($kdvRate / 100), 2);
                $lineTotal = round($lineNet + $lineKdv, 2);
            }
            $subtotal += $lineNet;
            $kdvTotal += $lineKdv;
            PurchaseItem::create([
                'purchaseId' => $purchase->id,
                'productId' => $row['productId'],
                'unitPrice' => $unitPrice,
                'listPrice' => $listPrice,
                'quantity' => $qty,
                'kdvRate' => $kdvRate,
                'lineTotal' => $lineTotal,
            ]);
        }
        $discRate = (float) ($purchase->supplierDiscountRate ?? 0);
        if ($discRate > 0 && $discRate <= 100) {
            $subtotal = round($subtotal * (1 - $discRate / 100), 2);
            $kdvTotal = round($kdvTotal * (1 - $discRate / 100), 2);
        }
        $grandTotal = round($subtotal + $kdvTotal, 2);
        $purchase->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
        $this->auditService->logUpdate('purchase', $purchase->id, [], $purchase->toArray());
        return redirect()->route('purchases.show', $purchase)->with('success', 'Alış güncellendi.');
    }

    public function cancel(Purchase $purchase)
    {
        $this->auditService->logCancel('purchase', $purchase->id);
        $purchase->update(['isCancelled' => true]);
        return redirect()->route('purchases.show', $purchase)->with('success', 'Alış iptal edildi.');
    }
}
