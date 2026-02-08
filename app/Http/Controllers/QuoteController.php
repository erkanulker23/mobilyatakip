<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Personnel;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request)
    {
        $q = Quote::with('customer')->orderBy('createdAt', 'desc');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('quoteNumber', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('customerId')) {
            $q->where('customerId', $request->customerId);
        }
        if ($request->filled('from')) {
            $q->whereDate('createdAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('createdAt', '<=', $request->to);
        }
        $quotes = $q->paginate(20)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        return view('quotes.index', compact('quotes', 'customers'));
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
            'kdvIncluded' => 'nullable|boolean',
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
            'items.*.lineDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountAmount' => 'nullable|numeric|min:0',
        ]);
        $kdvIncluded = $request->boolean('kdvIncluded');

        $quote = DB::transaction(function () use ($validated, $kdvIncluded) {
            $last = Quote::whereYear('createdAt', date('Y'))
                ->orderBy('quoteNumber', 'desc')
                ->lockForUpdate()
                ->first();
            $next = $last ? (int) preg_replace('/^TKL-\d+-/', '', $last->quoteNumber) + 1 : 1;
            $quoteNumber = 'TKL-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

            $quote = Quote::create([
                'quoteNumber' => $quoteNumber,
                'customerId' => $validated['customerId'],
                'status' => 'taslak',
                'kdvIncluded' => $kdvIncluded,
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
            $lineKdvSum = 0;
            foreach ($validated['items'] as $row) {
                $unitPrice = (float) $row['unitPrice'];
                $qty = (int) $row['quantity'];
                $kdvRate = (float) ($row['kdvRate'] ?? 18);
                $lineDiscPct = (float) ($row['lineDiscountPercent'] ?? 0);
                $lineDiscAmt = (float) ($row['lineDiscountAmount'] ?? 0);
                if ($kdvIncluded) {
                    $rawLineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                } else {
                    $rawLineNet = round($unitPrice * $qty, 2);
                }
                $lineDisc = round($rawLineNet * ($lineDiscPct / 100) + $lineDiscAmt, 2);
                $lineNet = max(0, round($rawLineNet - $lineDisc, 2));
                $lineKdv = round($lineNet * ($kdvRate / 100), 2);
                $lineTotal = round($lineNet + $lineKdv, 2);
                $subtotal += $lineNet;
                $lineKdvSum += $lineKdv;
                QuoteItem::create([
                    'quoteId' => $quote->id,
                    'productId' => $row['productId'],
                    'unitPrice' => $unitPrice,
                    'quantity' => $qty,
                    'kdvRate' => $kdvRate,
                    'lineDiscountPercent' => $lineDiscPct,
                    'lineDiscountAmount' => $lineDiscAmt,
                    'lineTotal' => $lineTotal,
                ]);
            }
            $generalDisc = round($subtotal * (($quote->generalDiscountPercent ?? 0) / 100) + (float) ($quote->generalDiscountAmount ?? 0), 2);
            $afterDisc = max(0, round($subtotal - $generalDisc, 2));
            $ratio = $subtotal > 0 ? $afterDisc / $subtotal : 0;
            $kdvTotal = round($ratio * $lineKdvSum, 2);
            $grandTotal = round($afterDisc + $kdvTotal, 2);
            $quote->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
            return $quote;
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['customer', 'personnel', 'items.product']);
        return view('quotes.show', compact('quote'));
    }

    public function print(Quote $quote)
    {
        $quote->load(['customer', 'personnel', 'items.product']);
        return view('quotes.print', compact('quote'));
    }

    public function email(Request $request, Quote $quote)
    {
        $quote->load(['customer', 'items.product']);
        return view('quotes.email', compact('quote'));
    }

    public function sendEmail(Request $request, Quote $quote)
    {
        $validated = $request->validate(['email' => 'required|email']);
        $quote->load(['customer', 'items.product']);
        // TODO: Mail gönderimi - Company mail ayarları ile
        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif e-posta ile gönderildi.');
    }

    public function convert(Quote $quote)
    {
        try {
            $sale = $this->saleService->createFromQuote($quote->id);
            return redirect()->route('sales.show', $sale)->with('success', 'Teklif satışa dönüştürüldü.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Quote $quote)
    {
        $quote->load('items.product');
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('supplier')->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        return view('quotes.edit', compact('quote', 'customers', 'products', 'personnel'));
    }

    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'kdvIncluded' => 'nullable|boolean',
            'generalDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'generalDiscountAmount' => 'nullable|numeric|min:0',
            'validUntil' => 'nullable|date',
            'notes' => 'nullable|string',
            'personnelId' => 'nullable|exists:personnel,id',
            'status' => 'nullable|in:taslak,onaylandi,reddedildi',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountAmount' => 'nullable|numeric|min:0',
        ]);
        $kdvIncluded = $request->boolean('kdvIncluded');
        $quote->update([
            'customerId' => $validated['customerId'],
            'kdvIncluded' => $kdvIncluded,
            'generalDiscountPercent' => $validated['generalDiscountPercent'] ?? 0,
            'generalDiscountAmount' => $validated['generalDiscountAmount'] ?? 0,
            'validUntil' => $validated['validUntil'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'personnelId' => $validated['personnelId'] ?? null,
            'status' => $validated['status'] ?? $quote->status,
        ]);
        $quote->items()->delete();
        $subtotal = 0;
        $lineKdvSum = 0;
        foreach ($validated['items'] as $row) {
            $unitPrice = (float) $row['unitPrice'];
            $qty = (int) $row['quantity'];
            $kdvRate = (float) ($row['kdvRate'] ?? 18);
            $lineDiscPct = (float) ($row['lineDiscountPercent'] ?? 0);
            $lineDiscAmt = (float) ($row['lineDiscountAmount'] ?? 0);
            if ($kdvIncluded) {
                $rawLineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                $rawLineTotal = round($unitPrice * $qty, 2);
            } else {
                $rawLineNet = round($unitPrice * $qty, 2);
                $rawLineTotal = round($rawLineNet * (1 + $kdvRate / 100), 2);
            }
            $lineDisc = round($rawLineNet * ($lineDiscPct / 100) + $lineDiscAmt, 2);
            $lineNet = max(0, round($rawLineNet - $lineDisc, 2));
            $lineKdv = round($lineNet * ($kdvRate / 100), 2);
            $lineTotal = round($lineNet + $lineKdv, 2);
            $subtotal += $lineNet;
            $lineKdvSum += $lineKdv;
            QuoteItem::create([
                'quoteId' => $quote->id,
                'productId' => $row['productId'],
                'unitPrice' => $unitPrice,
                'quantity' => $qty,
                'kdvRate' => $kdvRate,
                'lineDiscountPercent' => $lineDiscPct,
                'lineDiscountAmount' => $lineDiscAmt,
                'lineTotal' => $lineTotal,
            ]);
        }
        $generalDisc = round($subtotal * (($quote->generalDiscountPercent ?? 0) / 100) + (float) ($quote->generalDiscountAmount ?? 0), 2);
        $afterDisc = max(0, round($subtotal - $generalDisc, 2));
        $ratio = $subtotal > 0 ? $afterDisc / $subtotal : 0;
        $kdvTotal = round($ratio * $lineKdvSum, 2);
        $grandTotal = round($afterDisc + $kdvTotal, 2);
        $quote->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif güncellendi.');
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Teklif silindi.');
    }
}
