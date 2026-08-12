<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Personnel;
use App\Models\Branch;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\AuditService;
use App\Support\DrawingFiles;
use App\Support\ItemDescription;
use App\Support\QuoteCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $q = Quote::with(['customer', 'personnel', 'branch', 'createdByUser.personnel', 'convertedSale'])->orderBy('createdAt', 'desc');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('quoteNumber', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'taslak') {
                $q->where('status', 'taslak')->whereNull('convertedSaleId');
            } else {
                $q->where('status', $request->status);
            }
        }
        if ($request->filled('customerId')) {
            $q->where('customerId', $request->customerId);
        }
        if ($request->filled('personnelId')) {
            $q->where('personnelId', $request->personnelId);
        }
        if ($request->filled('branchId')) {
            if ($request->input('branchId') === 'none') {
                $q->whereNull('branchId');
            } else {
                $q->where('branchId', $request->input('branchId'));
            }
        }
        if ($request->filled('from')) {
            $q->whereDate('createdAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('createdAt', '<=', $request->to);
        }
        $quotes = $q->paginate(20)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $branches = Branch::forSelect(false);
        $quoteIds = $quotes->getCollection()->filter(fn ($q) => ! $q->convertedSaleId)->pluck('id')->values()->all();
        $creatorFallbackMap = QuoteCreator::creatorNameMapFromAudit($quotes->getCollection());

        return view('quotes.index', compact('quotes', 'customers', 'personnel', 'branches', 'quoteIds', 'creatorFallbackMap'));
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'required|uuid|exists:quotes,id']);
        $ids = $request->input('ids', []);
        $converted = Quote::whereIn('id', $ids)->whereNotNull('convertedSaleId')->pluck('quoteNumber')->toArray();
        if (! empty($converted)) {
            return redirect()->back()->with('error', 'Satışa dönüştürülmüş teklifler silinemez: ' . implode(', ', $converted));
        }
        $count = 0;
        foreach ($ids as $id) {
            $quote = Quote::find($id);
            if (! $quote) {
                continue;
            }
            $quoteNumber = $quote->quoteNumber;
            $quoteId = $quote->id;
            DB::transaction(function () use ($quote, $quoteId, $quoteNumber) {
                $quote->items()->delete();
                $quote->delete();
                $this->auditService->logDelete('quote', $quoteId, ['quoteNumber' => $quoteNumber]);
            });
            $count++;
        }

        return redirect()->route('quotes.index')->with('success', $count . ' teklif silindi.');
    }

    public function create()
    {
        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $initialProducts = collect();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $branches = Branch::forSelect();

        return view('quotes.create', compact('customers', 'initialProducts', 'personnel', 'branches'));
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
            'branchId' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'nullable|string',
            'items.*.productName' => 'nullable|string|max:255',
            'items.*.descriptionLines' => 'nullable|array|max:30',
            'items.*.descriptionLines.*' => 'nullable|string|max:500',
            'items.*.description' => 'nullable|string|max:3000',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountAmount' => 'nullable|numeric|min:0',
        ] + DrawingFiles::validationRules());

        $items = $this->mapQuoteItems($validated['items']);
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'En az bir geçerli kalem girin (ürün seçin veya manuel ürün adı yazın).');
        }

        $kdvIncluded = $request->boolean('kdvIncluded');

        $quote = DB::transaction(function () use ($validated, $kdvIncluded, $request, $items) {
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
                'branchId' => Personnel::resolveBranchId($validated['branchId'] ?? null, $validated['personnelId'] ?? null),
                'createdBy' => auth()->id() ?: null,
                'subtotal' => 0,
                'kdvTotal' => 0,
                'grandTotal' => 0,
            ]);

            $this->persistQuoteItems($quote, $items, $kdvIncluded);

            $drawingFiles = DrawingFiles::storeUploads($request, 'drawings/quotes');
            if ($drawingFiles !== []) {
                $quote->update(['drawingFiles' => $drawingFiles]);
            }

            return $quote;
        });

        $this->auditService->logCreate('quote', $quote->id, [
            'quoteNumber' => $quote->quoteNumber,
            'grandTotal' => $quote->grandTotal,
        ]);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['customer', 'personnel', 'branch', 'createdByUser.personnel', 'items.product']);

        return view('quotes.show', compact('quote'));
    }

    public function print(Quote $quote)
    {
        $quote->load(['customer', 'personnel', 'branch', 'createdByUser.personnel', 'items.product']);

        return view('quotes.print', compact('quote'));
    }

    public function email(Request $request, Quote $quote)
    {
        $quote->load(['customer', 'items.product']);

        return view('quotes.email', compact('quote'));
    }

    public function sendEmail(Request $request, Quote $quote)
    {
        $request->validate(['email' => 'required|email']);
        $quote->load(['customer', 'items.product']);
        $this->auditService->logAction('quote', $quote->id, 'email', ['quoteNumber' => $quote->quoteNumber]);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif e-posta ile gönderildi.');
    }

    public function convert(Quote $quote)
    {
        if ($quote->convertedSaleId) {
            $existing = Sale::find($quote->convertedSaleId);

            return redirect()->route('sales.show', $quote->convertedSaleId)
                ->with('info', 'Bu teklif zaten satışa dönüştürülmüş' . ($existing ? ': ' . $existing->saleNumber : '') . '.');
        }
        try {
            $sale = $this->saleService->createFromQuote($quote->id);
            $this->auditService->logAction('quote', $quote->id, 'convert', [
                'quoteNumber' => $quote->quoteNumber,
                'saleNumber' => $sale->saleNumber,
            ]);

            return redirect()->route('sales.show', $sale)->with('success', 'Teklif satışa dönüştürüldü.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Quote $quote)
    {
        if ($quote->convertedSaleId) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Satışa dönüştürülmüş teklif düzenlenemez.');
        }
        $quote->load(['items.product', 'branch']);
        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $productIds = $quote->items->pluck('productId')->filter()->unique()->values();
        $initialProducts = $productIds->isEmpty()
            ? collect()
            : Product::with('supplier:id,name')->whereIn('id', $productIds)->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $branches = Branch::forSelect();
        if ($quote->branchId && ! $branches->contains('id', $quote->branchId) && $quote->branch) {
            $branches = $branches->prepend($quote->branch);
        }

        return view('quotes.edit', compact('quote', 'customers', 'initialProducts', 'personnel', 'branches'));
    }

    public function update(Request $request, Quote $quote)
    {
        if ($quote->convertedSaleId) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Satışa dönüştürülmüş teklif düzenlenemez.');
        }

        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'kdvIncluded' => 'nullable|boolean',
            'generalDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'generalDiscountAmount' => 'nullable|numeric|min:0',
            'validUntil' => 'nullable|date',
            'notes' => 'nullable|string',
            'personnelId' => 'nullable|exists:personnel,id',
            'branchId' => 'nullable|exists:branches,id',
            'status' => 'nullable|in:taslak,onaylandi,reddedildi',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'nullable|string',
            'items.*.productName' => 'nullable|string|max:255',
            'items.*.descriptionLines' => 'nullable|array|max:30',
            'items.*.descriptionLines.*' => 'nullable|string|max:500',
            'items.*.description' => 'nullable|string|max:3000',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountAmount' => 'nullable|numeric|min:0',
        ] + DrawingFiles::validationRules());

        $items = $this->mapQuoteItems($validated['items']);
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'En az bir geçerli kalem girin (ürün seçin veya manuel ürün adı yazın).');
        }

        $kdvIncluded = $request->boolean('kdvIncluded');
        $quote->update([
            'customerId' => $validated['customerId'],
            'kdvIncluded' => $kdvIncluded,
            'generalDiscountPercent' => $validated['generalDiscountPercent'] ?? 0,
            'generalDiscountAmount' => $validated['generalDiscountAmount'] ?? 0,
            'validUntil' => $validated['validUntil'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'personnelId' => $validated['personnelId'] ?? null,
            'branchId' => Personnel::resolveBranchId($validated['branchId'] ?? null, $validated['personnelId'] ?? null),
            'status' => $validated['status'] ?? $quote->status,
            'drawingFiles' => DrawingFiles::syncFromRequest(
                $request,
                DrawingFiles::entries($quote->drawingFiles),
                'drawings/quotes'
            ),
        ]);
        $quote->items()->delete();
        $this->persistQuoteItems($quote, $items, $kdvIncluded);

        $this->auditService->logUpdate('quote', $quote->id, [], [
            'quoteNumber' => $quote->quoteNumber,
            'grandTotal' => $quote->grandTotal,
        ]);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif güncellendi.');
    }

    public function duplicate(Quote $quote)
    {
        $quote->load('items');

        $newQuote = DB::transaction(function () use ($quote) {
            $last = Quote::whereYear('createdAt', date('Y'))
                ->orderBy('quoteNumber', 'desc')
                ->lockForUpdate()
                ->first();
            $next = $last ? (int) preg_replace('/^TKL-\d+-/', '', $last->quoteNumber) + 1 : 1;
            $quoteNumber = 'TKL-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

            $drawingFiles = DrawingFiles::duplicateEntries(
                DrawingFiles::entries($quote->drawingFiles),
                'drawings/quotes'
            );

            $newQuote = Quote::create([
                'quoteNumber' => $quoteNumber,
                'customerId' => $quote->customerId,
                'status' => 'taslak',
                'kdvIncluded' => $quote->kdvIncluded,
                'generalDiscountPercent' => $quote->generalDiscountPercent ?? 0,
                'generalDiscountAmount' => $quote->generalDiscountAmount ?? 0,
                'revision' => 1,
                'validUntil' => $quote->validUntil,
                'notes' => $quote->notes,
                'personnelId' => $quote->personnelId,
                'branchId' => $quote->branchId,
                'createdBy' => auth()->id() ?: null,
                'customerSource' => $quote->customerSource,
                'subtotal' => $quote->subtotal,
                'kdvTotal' => $quote->kdvTotal,
                'grandTotal' => $quote->grandTotal,
                'drawingFiles' => $drawingFiles !== [] ? $drawingFiles : null,
            ]);

            foreach ($quote->items as $item) {
                QuoteItem::create([
                    'quoteId' => $newQuote->id,
                    'productId' => $item->productId,
                    'productName' => $item->productName,
                    'description' => $item->description,
                    'unitPrice' => $item->unitPrice,
                    'quantity' => $item->quantity,
                    'kdvRate' => $item->kdvRate,
                    'lineDiscountPercent' => $item->lineDiscountPercent,
                    'lineDiscountAmount' => $item->lineDiscountAmount,
                    'lineTotal' => $item->lineTotal,
                ]);
            }

            return $newQuote;
        });

        $this->auditService->logAction('quote', $newQuote->id, 'duplicate', [
            'quoteNumber' => $newQuote->quoteNumber,
            'sourceQuoteNumber' => $quote->quoteNumber,
            'sourceQuoteId' => $quote->id,
        ]);

        return redirect()->route('quotes.show', $newQuote)
            ->with('success', 'Teklif çoğaltıldı: ' . $newQuote->quoteNumber);
    }

    public function destroy(Quote $quote)
    {
        if ($quote->convertedSaleId) {
            return redirect()->back()->with('error', 'Satışa dönüştürülmüş teklif silinemez.');
        }
        $this->auditService->logDelete('quote', $quote->id, ['quoteNumber' => $quote->quoteNumber]);
        $quote->items()->delete();
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Teklif silindi.');
    }

    private function mapQuoteItems(array $rawItems): array
    {
        return collect($rawItems)->map(function ($item) {
            $product = ! empty($item['productId']) ? Product::find($item['productId']) : null;
            $description = ItemDescription::fromInput($item['descriptionLines'] ?? $item['description'] ?? null);
            $base = [
                'description' => $description,
                'unitPrice' => $item['unitPrice'],
                'quantity' => $item['quantity'],
                'kdvRate' => $item['kdvRate'] ?? 18,
                'lineDiscountPercent' => $item['lineDiscountPercent'] ?? null,
                'lineDiscountAmount' => $item['lineDiscountAmount'] ?? null,
            ];
            if ($product) {
                return array_merge($base, ['productId' => $product->id, 'productName' => null]);
            }
            $name = trim($item['productName'] ?? '') ?: trim($item['productId'] ?? '');

            return array_merge($base, ['productId' => null, 'productName' => $name]);
        })->filter(fn ($i) => ! empty($i['productId']) || ! empty($i['productName']))->values()->all();
    }

    private function persistQuoteItems(Quote $quote, array $items, bool $kdvIncluded): void
    {
        $subtotal = 0;
        $lineKdvSum = 0;
        foreach ($items as $row) {
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
                'productName' => $row['productName'],
                'description' => $row['description'],
                'unitPrice' => $unitPrice,
                'quantity' => $qty,
                'kdvRate' => $kdvRate,
                'lineDiscountPercent' => $lineDiscPct,
                'lineDiscountAmount' => $lineDiscAmt,
                'lineTotal' => $lineTotal,
            ]);
        }
        if ($kdvIncluded) {
            $grossBeforeGeneralDisc = round($subtotal + $lineKdvSum, 2);
            $generalDisc = round(
                $grossBeforeGeneralDisc * (($quote->generalDiscountPercent ?? 0) / 100)
                + (float) ($quote->generalDiscountAmount ?? 0),
                2
            );
            $grandTotal = max(0, round($grossBeforeGeneralDisc - $generalDisc, 2));
            $ratio = $grossBeforeGeneralDisc > 0 ? $grandTotal / $grossBeforeGeneralDisc : 0;
            $kdvTotal = round($ratio * $lineKdvSum, 2);
        } else {
            $generalDisc = round($subtotal * (($quote->generalDiscountPercent ?? 0) / 100) + (float) ($quote->generalDiscountAmount ?? 0), 2);
            $afterDisc = max(0, round($subtotal - $generalDisc, 2));
            $ratio = $subtotal > 0 ? $afterDisc / $subtotal : 0;
            $kdvTotal = round($ratio * $lineKdvSum, 2);
            $grandTotal = round($afterDisc + $kdvTotal, 2);
        }
        $quote->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);
    }
}
