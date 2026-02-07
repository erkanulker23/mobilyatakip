<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')->orderBy('createdAt', 'desc')->paginate(20);
        return view('purchases.index', compact('purchases'));
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
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
        ]);
        $purchaseNumber = 'ALS-' . date('Y') . '-' . str_pad((string) (Purchase::whereYear('createdAt', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT);
        $purchase = Purchase::create([
            'purchaseNumber' => $purchaseNumber,
            'supplierId' => $validated['supplierId'],
            'purchaseDate' => $validated['purchaseDate'],
            'dueDate' => $validated['dueDate'] ?? null,
            'subtotal' => 0,
            'kdvTotal' => 0,
            'grandTotal' => 0,
            'paidAmount' => 0,
            'notes' => $validated['notes'] ?? null,
        ]);
        $grandTotal = 0;
        foreach ($validated['items'] as $row) {
            $lineNet = (float) $row['unitPrice'] * (int) $row['quantity'];
            $kdvRate = (float) ($row['kdvRate'] ?? 18);
            $lineKdv = $lineNet * ($kdvRate / 100);
            $lineTotal = round($lineNet + $lineKdv, 2);
            \App\Models\PurchaseItem::create([
                'purchaseId' => $purchase->id,
                'productId' => $row['productId'],
                'unitPrice' => $row['unitPrice'],
                'quantity' => $row['quantity'],
                'kdvRate' => $kdvRate,
                'lineTotal' => $lineTotal,
            ]);
            $grandTotal += $lineTotal;
        }
        $purchase->update(['subtotal' => $grandTotal, 'kdvTotal' => 0, 'grandTotal' => $grandTotal]);
        return redirect()->route('purchases.show', $purchase)->with('success', 'Alış kaydedildi.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        return view('purchases.show', compact('purchase'));
    }
}
