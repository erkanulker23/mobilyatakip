<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Personnel;
use App\Services\SaleService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request)
    {
        $q = Quote::with('customer')->orderBy('createdAt', 'desc');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        $quotes = $q->paginate(20);
        return view('quotes.index', compact('quotes'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('supplier')->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        return view('quotes.create', compact('customers', 'products', 'personnel'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'generalDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'generalDiscountAmount' => 'nullable|numeric|min:0',
            'validUntil' => 'nullable|date',
            'notes' => 'nullable|string',
            'personnelId' => 'nullable|exists:personnel,id',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
        ]);
        // Basitleştirilmiş teklif oluşturma - QuoteService kullanılabilir
        $quoteNumber = 'TKL-' . date('Y') . '-' . str_pad((string) (Quote::whereYear('createdAt', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT);
        $quote = Quote::create([
            'quoteNumber' => $quoteNumber,
            'customerId' => $validated['customerId'],
            'status' => 'taslak',
            'generalDiscountPercent' => $validated['generalDiscountPercent'] ?? 0,
            'generalDiscountAmount' => $validated['generalDiscountAmount'] ?? 0,
            'revision' => 1,
            'validUntil' => $validated['validUntil'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'personnelId' => $validated['personnelId'] ?? null,
            'subtotal' => 0,
            'kdvTotal' => 0,
            'grandTotal' => 0,
        ]);
        $subtotal = 0;
        foreach ($validated['items'] as $row) {
            $lineNet = (float) $row['unitPrice'] * (int) $row['quantity'];
            $kdvRate = (float) ($row['kdvRate'] ?? 18);
            $lineKdv = $lineNet * ($kdvRate / 100);
            $lineTotal = round($lineNet + $lineKdv, 2);
            \App\Models\QuoteItem::create([
                'quoteId' => $quote->id,
                'productId' => $row['productId'],
                'unitPrice' => $row['unitPrice'],
                'quantity' => $row['quantity'],
                'kdvRate' => $kdvRate,
                'lineTotal' => $lineTotal,
            ]);
            $subtotal += $lineNet;
        }
        $generalDisc = $subtotal * (($quote->generalDiscountPercent ?? 0) / 100) + (float) ($quote->generalDiscountAmount ?? 0);
        $afterDisc = max(0, $subtotal - $generalDisc);
        $kdvTotal = \App\Models\QuoteItem::where('quoteId', $quote->id)->get()->sum(fn ($i) => (float) $i->lineTotal - (float) $i->lineTotal / (1 + (float) $i->kdvRate / 100));
        $grandTotal = $afterDisc + $kdvTotal;
        $quote->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['customer', 'personnel', 'items.product']);
        return view('quotes.show', compact('quote'));
    }

    public function convert(Request $request, Quote $quote)
    {
        $validated = $request->validate(['warehouseId' => 'required|exists:warehouses,id']);
        $sale = $this->saleService->createFromQuote($quote->id, $validated['warehouseId']);
        return redirect()->route('sales.show', $sale)->with('success', 'Teklif satışa dönüştürüldü.');
    }

    public function edit(Quote $quote)
    {
        $quote->load('items.product');
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        return view('quotes.edit', compact('quote', 'customers', 'products', 'personnel'));
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Teklif silindi.');
    }
}
