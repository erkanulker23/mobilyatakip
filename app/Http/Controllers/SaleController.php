<?php

namespace App\Http\Controllers;

use App\Mail\SaleNotificationToSupplier;
use App\Mail\SaleToCustomer;
use App\Models\Personnel;
use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Support\CustomerLedger;
use App\Models\Kasa;
use Illuminate\Support\Facades\Cache;
use App\Models\KasaHareket;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\MailConfigService;
use App\Services\SaleService;
use App\Services\StockService;
use App\Support\SaleDocument;
use App\Support\SaleDocumentNaming;
use App\Support\DrawingFiles;
use App\Support\ItemDescription;
use App\Support\SaleDelivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private StockService $stockService,
        private AuditService $auditService
    ) {}

    public function create()
    {
        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $initialProducts = collect();
        return view('sales.create', compact('customers', 'initialProducts', 'personnel', 'kasalar'));
    }

    public function store(Request $request)
    {
        if ($request->input('initialPaymentMode') === 'kapora' && $request->filled('depositAmount')) {
            $request->merge(['depositAmount' => money_parse($request->input('depositAmount'))]);
        }

        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'personnelId' => 'nullable|exists:personnel,id',
            'saleDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'needsFinalMeasurement' => 'nullable|boolean',
            'kdvIncluded' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'saleDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'grandTotalOverride' => 'nullable|numeric|min:0',
            'initialPaymentMode' => 'nullable|in:none,kapora,full',
            'depositAmount' => 'nullable|numeric|min:0',
            'depositPaymentType' => \App\Support\PaymentType::validationRule(),
            'depositKasaId' => 'nullable|exists:kasa,id',
            'sendCustomerEmail' => 'nullable|boolean',
            'customerEmailNote' => 'nullable|string|max:1000',
            'returnTo' => 'nullable|string|in:service-tickets/create',
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
        $items = collect($validated['items'])->map(function ($item) {
            $product = !empty($item['productId']) ? \App\Models\Product::find($item['productId']) : null;
            if ($product) {
                return ['productId' => $product->id, 'productName' => null, 'description' => ItemDescription::fromInput($item['descriptionLines'] ?? $item['description'] ?? null), 'unitPrice' => $item['unitPrice'], 'quantity' => $item['quantity'], 'kdvRate' => $item['kdvRate'] ?? 10, 'lineDiscountPercent' => $item['lineDiscountPercent'] ?? null, 'lineDiscountAmount' => $item['lineDiscountAmount'] ?? null];
            }
            $name = trim($item['productName'] ?? '') ?: trim($item['productId'] ?? '');
            return ['productId' => null, 'productName' => $name, 'description' => ItemDescription::fromInput($item['descriptionLines'] ?? $item['description'] ?? null), 'unitPrice' => $item['unitPrice'], 'quantity' => $item['quantity'], 'kdvRate' => $item['kdvRate'] ?? 10, 'lineDiscountPercent' => $item['lineDiscountPercent'] ?? null, 'lineDiscountAmount' => $item['lineDiscountAmount'] ?? null];
        })->filter(fn($i) => !empty($i['productId']) || !empty($i['productName']))->values()->all();
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'En az bir geçerli kalem girin (ürün seçin veya manuel ürün adı yazın).');
        }

        $paymentMode = $validated['initialPaymentMode'] ?? 'none';
        $depositPaymentType = $validated['depositPaymentType'] ?? 'nakit';
        $depositAmount = 0.0;
        $isFullPayment = $paymentMode === 'full';

        if ($paymentMode === 'kapora') {
            $depositAmount = (float) ($validated['depositAmount'] ?? 0);
            if ($depositAmount <= 0) {
                return redirect()->back()->withInput()->with('error', 'Kapora seçildi — lütfen kapora tutarını girin.');
            }
        }

        $kasaRequired = in_array($paymentMode, ['kapora', 'full'], true);
        $kasaError = \App\Support\PaymentType::validateKasaSelection(
            $validated['depositKasaId'] ?? null,
            $depositPaymentType,
            $kasaRequired
        );
        if ($kasaError) {
            return redirect()->back()->withInput()->with('error', $kasaError);
        }

        try {
            $sale = $this->saleService->createDirect([
                'customerId' => $validated['customerId'],
                'personnelId' => $validated['personnelId'] ?? null,
                'saleDate' => $validated['saleDate'],
                'dueDate' => $validated['dueDate'] ?? null,
                'needsFinalMeasurement' => $request->boolean('needsFinalMeasurement'),
                'kdvIncluded' => $request->boolean('kdvIncluded'),
                'notes' => $validated['notes'] ?? null,
                'saleDiscountPercent' => (float) ($validated['saleDiscountPercent'] ?? 0),
                'grandTotalOverride' => isset($validated['grandTotalOverride']) && $validated['grandTotalOverride'] > 0 ? (float) $validated['grandTotalOverride'] : null,
                'items' => $items,
            ]);

            $drawingFiles = DrawingFiles::storeUploads($request, 'drawings/sales');
            if ($drawingFiles !== []) {
                $sale->update(['drawingFiles' => $drawingFiles]);
            }

            if ($isFullPayment || $depositAmount > 0) {
                if ($isFullPayment) {
                    $depositAmount = (float) $sale->grandTotal;
                }
                if ($depositAmount <= 0) {
                    return redirect()->route('sales.show', $sale)
                        ->with('success', 'Satış oluşturuldu.')
                        ->with('error', 'Tahsilat kaydedilemedi: genel toplam sıfır.')
                        ->with('show_supplier_email_prompt', true);
                }
                if ($depositAmount > (float) $sale->grandTotal) {
                    return redirect()->route('sales.show', $sale)
                        ->with('success', 'Satış oluşturuldu.')
                        ->with('error', 'Tahsilat kaydedilemedi: tutar genel toplamdan fazla.')
                        ->with('show_supplier_email_prompt', true);
                }
                $paymentLabel = $isFullPayment ? 'Tam ödeme' : 'Kapora';
                $paymentNotes = $isFullPayment
                    ? 'Satış oluşturulurken tam ödeme alındı'
                    : 'Satış oluşturulurken alınan kapora';
                $this->recordSaleDeposit(
                    $sale,
                    $depositAmount,
                    $depositPaymentType,
                    $validated['depositKasaId'] ?? null,
                    $validated['saleDate'],
                    $paymentLabel,
                    $paymentNotes
                );
                $sale->refresh();
            }

            $this->auditService->logCreate('sale', $sale->id, ['saleNumber' => $sale->saleNumber, 'grandTotal' => $sale->grandTotal]);

            $emailSent = false;
            if ($request->boolean('sendCustomerEmail')) {
                $emailSent = $this->sendCustomerEmailForSale($sale, $validated['customerEmailNote'] ?? null);
            }

            $message = 'Satış oluşturuldu.';
            if ($isFullPayment && $depositAmount > 0) {
                $message .= ' Tam ödeme: ' . number_format($depositAmount, 0, ',', '.') . ' ₺ kaydedildi.';
            } elseif ($depositAmount > 0) {
                $message .= ' Kapora: ' . number_format($depositAmount, 0, ',', '.') . ' ₺ kaydedildi.';
            }
            if ($emailSent) {
                $message .= ' Müşteriye e-posta gönderildi.';
            } elseif ($request->boolean('sendCustomerEmail')) {
                if ($request->input('returnTo') === 'service-tickets/create') {
                    return redirect()->route('service-tickets.create', [
                        'customerId' => $sale->customerId,
                        'saleId' => $sale->id,
                    ])->with('success', $message . ' Servis kaydını tamamlayabilirsiniz.')
                      ->with('error', 'Satış kaydedildi ancak müşteriye e-posta gönderilemedi.');
                }
                return redirect()->route('sales.show', $sale)
                    ->with('success', $message)
                    ->with('error', 'Satış kaydedildi ancak müşteriye e-posta gönderilemedi. Müşteri e-posta adresini kontrol edin.')
                    ->with('show_supplier_email_prompt', true);
            }

            if ($request->input('returnTo') === 'service-tickets/create') {
                return redirect()->route('service-tickets.create', [
                    'customerId' => $sale->customerId,
                    'saleId' => $sale->id,
                ])->with('success', 'Sipariş oluşturuldu. Servis kaydını tamamlayabilirsiniz.');
            }

            return redirect()->route('sales.show', $sale)
                ->with('success', $message)
                ->with('show_supplier_email_prompt', true);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $activeFilters = $this->salesIndexHasFilters($request);
        $q = $this->salesIndexQuery($request);
        $statsQuery = clone $q;
        $sales = $q->paginate(20)->withQueryString();
        $saleIds = $sales->getCollection()->pluck('id')->values()->all();

        if ($request->header('X-List-Partial') === '1') {
            return view('sales.partials.index-results', compact('sales', 'saleIds', 'activeFilters'));
        }

        $filterCustomers = collect();
        if ($request->filled('customerId')) {
            $filterCustomers = Customer::where('id', $request->customerId)->orderBy('name')->get(['id', 'name']);
        }

        $stats = $activeFilters
            ? $this->salesIndexStatsFromQuery($statsQuery, (int) $sales->total())
            : Cache::remember('sales.index.stats', now()->addMinutes(2), fn () => $this->salesIndexStatsFromQuery(
                Sale::query()->where('isCancelled', false),
                (int) Sale::where('isCancelled', false)->count(),
            ));

        return view('sales.index', compact('sales', 'filterCustomers', 'saleIds', 'stats', 'activeFilters'));
    }

    public function delivered(Request $request)
    {
        $activeFilters = $this->salesDeliveredHasFilters($request);
        $q = $this->salesDeliveredQuery($request);
        $statsQuery = clone $q;
        $sales = $q->paginate(20)->withQueryString();
        $saleIds = $sales->getCollection()->pluck('id')->values()->all();

        if ($request->header('X-List-Partial') === '1') {
            return view('sales.partials.index-results', [
                'sales' => $sales,
                'saleIds' => $saleIds,
                'activeFilters' => $activeFilters,
                'listContext' => 'delivered',
            ]);
        }

        $filterCustomers = collect();
        if ($request->filled('customerId')) {
            $filterCustomers = Customer::where('id', $request->customerId)->orderBy('name')->get(['id', 'name']);
        }

        $stats = $this->salesDeliveredStatsFromQuery($statsQuery, (int) $sales->total());

        return view('sales.delivered', compact('sales', 'filterCustomers', 'saleIds', 'stats', 'activeFilters'));
    }

    private function salesDeliveredHasFilters(Request $request): bool
    {
        return $request->filled('search')
            || $request->filled('customerId')
            || $request->filled('personnelId')
            || in_array($request->input('paymentStatus'), ['borclu', 'alacakli', 'odendi'], true)
            || $request->filled('from')
            || $request->filled('to');
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Sale> */
    private function salesDeliveredQuery(Request $request)
    {
        $q = Sale::with(['customer', 'personnel'])
            ->where('isCancelled', false)
            ->delivered()
            ->orderByDesc('deliveredAt')
            ->orderByDesc('createdAt');

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
        if ($request->filled('personnelId')) {
            $q->where('personnelId', $request->personnelId);
        }
        if ($request->filled('from')) {
            $q->whereDate('deliveredAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('deliveredAt', '<=', $request->to);
        }

        if ($request->filled('paymentStatus')) {
            match ($request->paymentStatus) {
                'borclu' => $q->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005'),
                'alacakli' => $q->whereRaw('grandTotal - COALESCE(paidAmount, 0) < -0.005'),
                'odendi' => $q->whereRaw('ABS(grandTotal - COALESCE(paidAmount, 0)) <= 0.005 AND grandTotal > 0'),
                default => null,
            };
        }

        return $q;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Sale>  $query */
    private function salesDeliveredStatsFromQuery($query, int $total): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return [
            'total' => $total,
            'turnover' => (float) (clone $query)->sum('grandTotal'),
            'receivable' => (float) (clone $query)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as total')
                ->value('total'),
            'withDebt' => (int) (clone $query)
                ->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005')
                ->count(),
            'thisMonth' => (int) (clone $query)
                ->whereDate('deliveredAt', '>=', $monthStart)
                ->whereDate('deliveredAt', '<=', $monthEnd)
                ->count(),
        ];
    }

    private function salesIndexHasFilters(Request $request): bool
    {
        return $request->filled('search')
            || $request->filled('customerId')
            || $request->filled('personnelId')
            || SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            || in_array($request->input('paymentStatus'), ['borclu', 'alacakli', 'odendi'], true)
            || $request->filled('from')
            || $request->filled('to');
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Sale> */
    private function salesIndexQuery(Request $request)
    {
        $q = Sale::with(['customer', 'personnel'])->where('isCancelled', false)->orderBy('createdAt', 'desc');

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
        if ($request->filled('personnelId')) {
            $q->where('personnelId', $request->personnelId);
        }
        if ($request->filled('from')) {
            $q->whereDate('saleDate', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('saleDate', '<=', $request->to);
        }

        $deliveryStatus = SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            ? $request->input('deliveryStatus')
            : null;
        if ($deliveryStatus) {
            SaleDelivery::applyDeliveryFilter($q, $deliveryStatus);
        }

        if ($request->filled('paymentStatus')) {
            match ($request->paymentStatus) {
                'borclu' => $q->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005'),
                'alacakli' => $q->whereRaw('grandTotal - COALESCE(paidAmount, 0) < -0.005'),
                'odendi' => $q->whereRaw('ABS(grandTotal - COALESCE(paidAmount, 0)) <= 0.005 AND grandTotal > 0'),
                default => null,
            };
        }

        return $q;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Sale>  $query */
    private function salesIndexStatsFromQuery($query, int $total): array
    {
        return [
            'total' => $total,
            'receivable' => (float) (clone $query)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as total')
                ->value('total'),
            'withDebt' => (int) (clone $query)
                ->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005')
                ->count(),
            'finalMeasurement' => (int) (clone $query)
                ->where('needsFinalMeasurement', true)
                ->pendingDelivery()
                ->count(),
        ];
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'required|uuid|exists:sales,id']);
        $ids = $request->input('ids', []);
        $withPayment = Sale::whereIn('id', $ids)->where(function ($q) {
            $q->where('paidAmount', '>', 0)->orWhereHas('payments');
        })->pluck('saleNumber')->toArray();
        if (!empty($withPayment)) {
            return redirect()->back()->with('error', 'Ödeme alınmış satışlar silinemez: ' . implode(', ', $withPayment));
        }
        $count = 0;
        foreach ($ids as $id) {
            $sale = Sale::find($id);
            if (!$sale) {
                continue;
            }
            $saleNumber = $sale->saleNumber;
            $grandTotal = (float) $sale->grandTotal;
            $saleId = $sale->id;
            DB::transaction(function () use ($sale, $saleId, $saleNumber, $grandTotal) {
                Quote::where('convertedSaleId', $saleId)->update(['convertedSaleId' => null]);
                CustomerPayment::where('saleId', $saleId)->update(['saleId' => null]);
                $this->reverseSaleStock($saleId, $saleNumber, 'satis_silme');
                $sale->items()->delete();
                $sale->delete();
                $this->auditService->logDelete('sale', $saleId, ['saleNumber' => $saleNumber, 'grandTotal' => $grandTotal]);
            });
            $count++;
        }
        return redirect()->route('sales.index')->with('success', $count . ' satış silindi.');
    }

    public function edit(Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş satış düzenlenemez.');
        }
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $productIds = $sale->items->pluck('productId')->filter()->unique()->values();
        $initialProducts = $productIds->isEmpty()
            ? collect()
            : Product::with('supplier:id,name')->whereIn('id', $productIds)->get();
        return view('sales.edit', compact('sale', 'customers', 'initialProducts', 'personnel'));
    }

    public function update(Request $request, Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş satış güncellenemez.');
        }
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'personnelId' => 'nullable|exists:personnel,id',
            'saleDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'needsFinalMeasurement' => 'nullable|boolean',
            'kdvIncluded' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'saleDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'grandTotalOverride' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|string|exists:sale_items,id',
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
        $items = collect($validated['items'])->map(function ($item) {
            $product = !empty($item['productId']) ? \App\Models\Product::find($item['productId']) : null;
            $name = $product ? null : (trim($item['productName'] ?? '') ?: trim($item['productId'] ?? ''));
            return [
                'id' => $item['id'] ?? null,
                'productId' => $product ? $product->id : null,
                'productName' => $name,
                'description' => ItemDescription::fromInput($item['descriptionLines'] ?? $item['description'] ?? null),
                'unitPrice' => (float) $item['unitPrice'],
                'quantity' => (int) $item['quantity'],
                'kdvRate' => (float) ($item['kdvRate'] ?? 10),
                'lineDiscountPercent' => (float) ($item['lineDiscountPercent'] ?? 0),
                'lineDiscountAmount' => (float) ($item['lineDiscountAmount'] ?? 0),
            ];
        })->filter(fn ($i) => !empty($i['productId']) || !empty($i['productName']) || ($i['id'] ?? null))->values()->all();
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'En az bir geçerli kalem girin.');
        }
        try {
            $sale = $this->saleService->find($sale->id);
            if (!$sale) {
                abort(404);
            }
            $sale->load('items');
            $oldAudit = [
                'saleNumber' => $sale->saleNumber,
                'notes' => $sale->notes,
                'grandTotal' => round((float) $sale->grandTotal, 2),
                'customerId' => $sale->customerId,
                'personnelId' => $sale->personnelId,
                'dueDate' => $sale->dueDate?->format('Y-m-d'),
                'saleDate' => $sale->saleDate?->format('Y-m-d'),
                'needsFinalMeasurement' => (bool) $sale->needsFinalMeasurement,
                'saleDiscountPercent' => round((float) ($sale->saleDiscountPercent ?? 0), 2),
                'itemsFingerprint' => $this->saleItemsFingerprint($sale),
            ];
            $oldCustomerId = $sale->customerId;
            $sale->update([
                'customerId' => $validated['customerId'],
                'personnelId' => $validated['personnelId'] ?? null,
                'saleDate' => $validated['saleDate'],
                'dueDate' => $validated['dueDate'] ?? null,
                'needsFinalMeasurement' => $request->boolean('needsFinalMeasurement'),
                'kdvIncluded' => $request->boolean('kdvIncluded'),
                'notes' => $validated['notes'] ?? null,
                'drawingFiles' => DrawingFiles::syncFromRequest(
                    $request,
                    DrawingFiles::entries($sale->drawingFiles),
                    'drawings/sales'
                ),
            ]);
            if ($validated['customerId'] !== $oldCustomerId) {
                CustomerPayment::where('saleId', $sale->id)->update(['customerId' => $validated['customerId']]);
            }
            $sale = $this->saleService->updateSaleItems($sale, $items, [
                'saleDiscountPercent' => (float) ($validated['saleDiscountPercent'] ?? 0),
                'grandTotalOverride' => isset($validated['grandTotalOverride']) && $validated['grandTotalOverride'] > 0
                    ? (float) $validated['grandTotalOverride'] : null,
            ]);
            $paidAmount = (float) ($sale->paidAmount ?? 0);
            if ((float) $sale->grandTotal + 0.005 < $paidAmount) {
                throw new \RuntimeException(
                    'Genel toplam (' . number_format((float) $sale->grandTotal, 0, ',', '.') . ' ₺), '
                    . 'tahsil edilen tutardan (' . number_format($paidAmount, 0, ',', '.') . ' ₺) düşük olamaz.'
                );
            }
            $this->auditService->logUpdate('sale', $sale->id, $oldAudit, [
                'saleNumber' => $sale->saleNumber,
                'notes' => $sale->notes,
                'grandTotal' => round((float) $sale->grandTotal, 2),
                'customerId' => $sale->customerId,
                'personnelId' => $sale->personnelId,
                'dueDate' => $sale->dueDate?->format('Y-m-d'),
                'saleDate' => $sale->saleDate?->format('Y-m-d'),
                'needsFinalMeasurement' => (bool) $sale->needsFinalMeasurement,
                'saleDiscountPercent' => round((float) ($sale->saleDiscountPercent ?? 0), 2),
                'itemsChanged' => $oldAudit['itemsFingerprint'] !== $this->saleItemsFingerprint($sale->load('items')),
            ]);
            return redirect()->route('sales.show', $sale)->with('success', 'Satış güncellendi.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        // Aynı müşteriden alınan ancak faturaya bağlı olmayan tahsilatlar (satış tarihinden sonra) — timeline'da gösterilebilir
        $unlinkedPayments = collect();
        if ($sale->customerId && $sale->saleDate) {
            $unlinkedPayments = CustomerPayment::where('customerId', $sale->customerId)
                ->whereNull('saleId')
                ->where('paymentDate', '>=', $sale->saleDate)
                ->orderBy('paymentDate', 'desc')
                ->get();
        }
        $saleRemaining = \App\Support\CustomerBalance::saleRemaining($sale);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $suppliers = Supplier::where('isActive', true)->orderBy('name')->get();

        $productionStagesReady = \App\Support\SaleProductionStageSchema::isReady();
        $productionStages = collect();
        $openDeficienciesCount = 0;
        $canAddProductionStage = ! ($sale->isCancelled ?? false);

        if ($productionStagesReady) {
            $sale->load(['items.product']);
            $productionStages = $sale->productionStages()
                ->with(['user.personnel', 'completedByUser', 'saleItem.product'])
                ->orderByDesc('actionDate')
                ->get();

            $openDeficienciesCount = $productionStages
                ->where('type', \App\Models\SaleProductionStage::TYPE_DEFICIENCY)
                ->where('isCompleted', false)
                ->count();
        }

        $customerLedger = $sale->customer
            ? CustomerLedger::detailData($sale->customer)
            : null;

        return view('sales.show', compact(
            'sale',
            'unlinkedPayments',
            'saleRemaining',
            'kasalar',
            'suppliers',
            'productionStages',
            'productionStagesReady',
            'canAddProductionStage',
            'openDeficienciesCount',
            'customerLedger',
        ));
    }

    public function print(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        return view('sales.print', array_merge(compact('sale'), SaleDocument::invoiceParams($sale)));
    }

    public function shipment(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        if ($sale->isCancelled ?? false) {
            abort(404);
        }
        return view('sales.shipment', compact('sale'));
    }

    public function workshopKoltuk(Sale $sale)
    {
        return $this->workshopSlip($sale, 'koltuk');
    }

    public function workshopMobilya(Sale $sale)
    {
        return $this->workshopSlip($sale, 'mobilya');
    }

    private function workshopSlip(Sale $sale, string $variant)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        if ($sale->isCancelled ?? false) {
            abort(404);
        }

        $slipParams = auth()->user()?->hideCommercialData()
            ? \App\Support\SaleDocument::slipParamsForWorkshopStaff($sale, $variant)
            : \App\Support\SaleDocument::slipParams($sale, $variant);

        return view('sales.workshop', compact('sale', 'variant', 'slipParams'));
    }

    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş siparişin durumu güncellenemez.');
        }

        $validated = $request->validate([
            'deliveryStatus' => 'required|' . \App\Support\SaleDelivery::validationRule(),
            'deliveredAt' => 'nullable|date|required_if:deliveryStatus,delivered',
        ], [
            'deliveredAt.required_if' => 'Teslim tarihi seçilmelidir.',
        ]);

        $fromStatus = \App\Support\SaleDelivery::currentStatus($sale);
        $status = $validated['deliveryStatus'];
        if ($status === \App\Support\SaleDelivery::DELIVERED) {
            $deliveredAt = Carbon::parse($validated['deliveredAt'])->startOfDay();
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::DELIVERED,
                'deliveredAt' => $deliveredAt,
                'needsFinalMeasurement' => false,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::DELIVERED, $deliveredAt);
            $message = 'Sipariş teslim edildi olarak işaretlendi.';
        } elseif ($status === \App\Support\SaleDelivery::SSH) {
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::SSH,
                'deliveredAt' => null,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::SSH);
            $message = 'Sipariş SSH var olarak işaretlendi.';
        } elseif ($status === \App\Support\SaleDelivery::IN_PRODUCTION) {
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::IN_PRODUCTION,
                'deliveredAt' => null,
                'workshopCompletedAt' => null,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::IN_PRODUCTION);
            $message = 'Sipariş üretimde olarak işaretlendi.';
        } elseif ($status === \App\Support\SaleDelivery::IN_DISCUSSION) {
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::IN_DISCUSSION,
                'deliveredAt' => null,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::IN_DISCUSSION);
            $message = 'Sipariş halen görüşülüyor olarak işaretlendi.';
        } elseif ($status === \App\Support\SaleDelivery::PARTIALLY_DELIVERED) {
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::PARTIALLY_DELIVERED,
                'deliveredAt' => null,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::PARTIALLY_DELIVERED);
            $message = 'Sipariş eksik teslim edildi olarak işaretlendi.';
        } else {
            $sale->update([
                'orderStatus' => \App\Support\SaleDelivery::PENDING,
                'deliveredAt' => null,
            ]);
            SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::PENDING);
            $message = 'Sipariş teslim bekliyor olarak güncellendi.';
        }

        $this->auditService->logAction('sale', $sale->id, 'status', [
            'saleNumber' => $sale->saleNumber,
            'status' => $status,
            'statusLabel' => \App\Support\SaleDelivery::label($status),
            'fromStatusLabel' => \App\Support\SaleDelivery::label($fromStatus),
        ]);

        return redirect()->route('sales.show', $sale)->with('success', $message);
    }

    public function convertToQuote(Sale $sale)
    {
        try {
            $quote = $this->saleService->createQuoteFromSale($sale->id);
            $this->auditService->logAction('sale', $sale->id, 'convert_to_quote', [
                'saleNumber' => $sale->saleNumber,
                'quoteNumber' => $quote->quoteNumber,
            ]);
            $this->auditService->logCreate('quote', $quote->id, [
                'quoteNumber' => $quote->quoteNumber,
                'fromSaleNumber' => $sale->saleNumber,
            ]);

            return redirect()->route('quotes.show', $quote)->with('success', 'Kayıt teklif olarak düzenlendi. Eski satış kaydı listeden kaldırıldı: ' . $quote->quoteNumber);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function shipmentPdf(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        if ($sale->isCancelled ?? false) {
            abort(404);
        }
        $filename = SaleDocumentNaming::downloadFilename($sale, SaleDocumentNaming::TYPE_SHIPMENT);
        $pdf = Pdf::loadView('sales.shipment-pdf', array_merge(
            SaleDocument::shipmentParams($sale),
            ['company' => \App\Models\Company::first()]
        ))->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function pdf(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        $filename = SaleDocumentNaming::downloadFilename($sale, SaleDocumentNaming::TYPE_ORDER);
        $pdf = Pdf::loadView('sales.pdf', array_merge(compact('sale'), SaleDocument::invoiceParams($sale)))
            ->setPaper('a4');

        return $pdf->download($filename);
    }

    public function destroy(Sale $sale)
    {
        $paidAmount = (float) ($sale->paidAmount ?? 0);
        $hasPayments = CustomerPayment::where('saleId', $sale->id)->exists();
        if ($paidAmount > 0 || $hasPayments) {
            return redirect()->back()->with('error', 'Ödeme alınmış satış silinemez. Önce tahsilatları iptal edin veya satışı iptal edin.');
        }
        $saleNumber = $sale->saleNumber;
        $grandTotal = (float) $sale->grandTotal;
        $saleId = $sale->id;
        DB::transaction(function () use ($sale, $saleId, $saleNumber, $grandTotal) {
            Quote::where('convertedSaleId', $saleId)->update(['convertedSaleId' => null]);
            CustomerPayment::where('saleId', $saleId)->update(['saleId' => null]);
            if (! $sale->isCancelled) {
                $this->reverseSaleStock($saleId, $saleNumber, 'satis_silme');
            }
            $sale->items()->delete();
            $sale->delete();
            $this->auditService->logDelete('sale', $saleId, ['saleNumber' => $saleNumber, 'grandTotal' => $grandTotal]);
        });
        return redirect()->route('sales.index')->with('success', 'Satış silindi.');
    }

    public function cancel(Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'Bu satış zaten iptal edilmiş.');
        }
        DB::transaction(function () use ($sale) {
            $this->reverseSaleStock($sale->id, $sale->saleNumber, 'satis_iptal');
            $sale->update(['isCancelled' => true]);
        });
        SaleActivity::create([
            'saleId' => $sale->id,
            'type' => SaleActivity::TYPE_STATUS_CHANGED,
            'description' => 'Sipariş iptal edildi',
            'metadata' => ['cancelled' => true],
        ]);
        $this->auditService->logCancel('sale', $sale->id);
        return redirect()->route('sales.show', $sale)->with('success', 'Sipariş iptal edildi.');
    }

    public function markDelivered(Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş satış teslim edildi olarak işaretlenemez.');
        }
        if (\App\Support\SaleDelivery::isDelivered($sale)) {
            return redirect()->route('sales.show', $sale)->with('info', 'Bu satış zaten teslim edildi olarak işaretli.');
        }
        $fromStatus = \App\Support\SaleDelivery::currentStatus($sale);
        $deliveredAt = now()->startOfDay();
        $sale->update([
            'orderStatus' => 'delivered',
            'deliveredAt' => $deliveredAt,
            'needsFinalMeasurement' => false,
        ]);
        SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::DELIVERED, $deliveredAt);
        return redirect()->route('sales.show', $sale)->with('success', 'Satış teslim edildi olarak işaretlendi.');
    }

    public function unmarkDelivered(Sale $sale)
    {
        if (! \App\Support\SaleDelivery::isDelivered($sale)) {
            return redirect()->route('sales.show', $sale)->with('info', 'Bu satış teslim edildi olarak işaretli değil.');
        }
        $fromStatus = \App\Support\SaleDelivery::currentStatus($sale);
        $sale->update([
            'orderStatus' => 'pending',
            'deliveredAt' => null,
        ]);
        SaleActivity::logStatusChange($sale->fresh(), $fromStatus, \App\Support\SaleDelivery::PENDING);
        return redirect()->route('sales.show', $sale)->with('success', 'Teslim işareti kaldırıldı.');
    }

    /** Satış iptal/silme: Net stok çıkışını (satis − satis_iade) depoya iade eder. */
    private function reverseSaleStock(string $saleId, string $saleNumber, string $refType): void
    {
        $this->stockService->reverseNetSaleStock($saleId, $saleNumber, $refType);
    }

    public function sendSupplierEmail(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        $suppliers = $sale->getSuppliersWithEmail();
        if ($suppliers->isEmpty()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Bu satışta e-posta adresi tanımlı tedarikçi bulunamadı.');
        }
        app(\App\Services\MailConfigService::class)->apply();
        $sent = [];
        foreach ($suppliers as $supplier) {
            try {
                Mail::to($supplier->email)->send(new SaleNotificationToSupplier($sale, $supplier));
                $sent[] = ['id' => $supplier->id, 'name' => $supplier->name, 'email' => $supplier->email];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Tedarikçi e-posta gönderim hatası', ['sale' => $sale->id, 'exception' => $e->getMessage()]);
                return redirect()->route('sales.show', $sale)
                    ->with('error', 'E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
            }
        }
        SaleActivity::create([
            'saleId' => $sale->id,
            'type' => SaleActivity::TYPE_SUPPLIER_EMAIL_SENT,
            'description' => 'Tedarikçiye sipariş maili gönderildi',
            'metadata' => ['suppliers' => $sent],
        ]);
        return redirect()->route('sales.show', $sale)
            ->with('success', count($sent) . ' tedarikçiye sipariş maili gönderildi.');
    }

    public function sendCustomerEmail(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'note' => 'nullable|string|max:1000',
        ]);
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        $to = $validated['email'] ?? $sale->customer?->email;
        if (!$to) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Müşterinin e-posta adresi yok. Müşteri kartına e-posta ekleyin veya gönderim sırasında bir adres girin.');
        }
        if (!$this->sendCustomerEmailForSale($sale, $validated['note'] ?? null, $to)) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'E-posta gönderilemedi. SMTP ayarlarını kontrol edin.');
        }
        return redirect()->route('sales.show', $sale)
            ->with('success', $to . ' adresine sipariş gönderildi.');
    }

    public function addActivity(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . SaleActivity::TYPE_SUPPLIER_EMAIL_READ . ',' . SaleActivity::TYPE_SUPPLIER_EMAIL_REPLIED,
            'supplierId' => 'nullable|exists:suppliers,id',
        ]);
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        $descriptions = [
            SaleActivity::TYPE_SUPPLIER_EMAIL_READ => 'Tedarikçi e-postayı okudu',
            SaleActivity::TYPE_SUPPLIER_EMAIL_REPLIED => 'Tedarikçi e-postayı cevapladı',
        ];
        SaleActivity::create([
            'saleId' => $sale->id,
            'type' => $validated['type'],
            'description' => $descriptions[$validated['type']],
            'metadata' => $validated['supplierId'] ? ['supplierId' => $validated['supplierId']] : null,
        ]);
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Zaman çizelgesi güncellendi.');
    }

    private function recordSaleDeposit(
        Sale $sale,
        float $amount,
        string $paymentType,
        ?string $kasaId,
        string $paymentDate,
        string $paymentLabel = 'Kapora',
        ?string $paymentNotes = null
    ): void {
        DB::transaction(function () use ($sale, $amount, $paymentType, $kasaId, $paymentDate, $paymentLabel, $paymentNotes) {
            if ($kasaId) {
                $kasa = Kasa::find($kasaId);
                if ($kasa) {
                    $paymentType = \App\Support\PaymentType::syncPaymentTypeWithKasa($paymentType, $kasa);
                }
            }

            $payment = CustomerPayment::create([
                'customerId' => $sale->customerId,
                'saleId' => $sale->id,
                'amount' => $amount,
                'paymentDate' => $paymentDate,
                'paymentType' => $paymentType,
                'kasaId' => $kasaId,
                'notes' => $paymentNotes ?? 'Satış oluşturulurken alınan kapora',
            ]);
            $sale->increment('paidAmount', $amount);
            $this->auditService->logCreate('customer_payment', $payment->id, [
                'amount' => $amount,
                'customerId' => $sale->customerId,
                'saleId' => $sale->id,
            ]);

            if ($kasaId && in_array($paymentType, ['nakit', 'havale', 'kredi_karti'], true)) {
                $paymentTypeLabel = \App\Support\PaymentType::label($paymentType);
                if ($paymentTypeLabel === '—') {
                    $paymentTypeLabel = '';
                }
                $desc = $paymentLabel . ' - ' . ($sale->customer?->name ?? 'Müşteri');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                $desc .= ' - Sipariş: ' . $sale->saleNumber;

                KasaHareket::create([
                    'kasaId' => $kasaId,
                    'type' => 'giris',
                    'amount' => $amount,
                    'movementDate' => $paymentDate,
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'customer_payment',
                    'refId' => $payment->id,
                ]);
            }
        });
    }

    private function sendCustomerEmailForSale(Sale $sale, ?string $note = null, ?string $to = null): bool
    {
        $to = $to ?: $sale->customer?->email;
        if (!$to) {
            return false;
        }
        app(MailConfigService::class)->apply();
        try {
            Mail::to($to)->send(new SaleToCustomer($sale->fresh(['customer', 'personnel', 'items.product']), $note));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Müşteri e-posta gönderim hatası', ['sale' => $sale->id, 'exception' => $e->getMessage()]);
            return false;
        }
        SaleActivity::create([
            'saleId' => $sale->id,
            'type' => SaleActivity::TYPE_CUSTOMER_EMAIL_SENT,
            'description' => 'Müşteriye sipariş maili gönderildi',
            'metadata' => ['email' => $to],
        ]);

        return true;
    }

    private function saleItemsFingerprint(Sale $sale): string
    {
        $payload = $sale->items->map(fn ($item) => [
            (string) ($item->productId ?? ''),
            (string) ($item->productName ?? ''),
            (string) ($item->description ?? ''),
            round((float) ($item->unitPrice ?? 0), 2),
            (int) ($item->quantity ?? 0),
            round((float) ($item->lineDiscountPercent ?? 0), 2),
            round((float) ($item->lineDiscountAmount ?? 0), 2),
        ])->values()->all();

        return md5(json_encode($payload));
    }
}
