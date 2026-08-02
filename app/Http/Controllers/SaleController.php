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
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\MailConfigService;
use App\Services\SaleService;
use App\Services\StockService;
use App\Support\SaleDocument;
use App\Support\DrawingFiles;
use App\Support\ItemDescription;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $products = Product::where('isActive', true)->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        return view('sales.create', compact('customers', 'products', 'personnel', 'kasalar'));
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
                        ->with('show_supplier_email_prompt', true)
                        ->with('show_sale_actions', true);
                }
                if ($depositAmount > (float) $sale->grandTotal) {
                    return redirect()->route('sales.show', $sale)
                        ->with('success', 'Satış oluşturuldu.')
                        ->with('error', 'Tahsilat kaydedilemedi: tutar genel toplamdan fazla.')
                        ->with('show_supplier_email_prompt', true)
                        ->with('show_sale_actions', true);
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
                    ->with('show_supplier_email_prompt', true)
                    ->with('show_sale_actions', true);
            }

            if ($request->input('returnTo') === 'service-tickets/create') {
                return redirect()->route('service-tickets.create', [
                    'customerId' => $sale->customerId,
                    'saleId' => $sale->id,
                ])->with('success', 'Sipariş oluşturuldu. Servis kaydını tamamlayabilirsiniz.');
            }

            return redirect()->route('sales.show', $sale)
                ->with('success', $message)
                ->with('show_supplier_email_prompt', true)
                ->with('show_sale_actions', true);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $q = Sale::with(['customer', 'personnel'])->orderBy('createdAt', 'desc');
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
        if ($request->filled('needsFinalMeasurement')) {
            $q->where('needsFinalMeasurement', $request->needsFinalMeasurement === '1');
        }
        if ($request->filled('deliveryStatus')) {
            if ($request->deliveryStatus === 'delivered') {
                $q->whereNotNull('deliveredAt')->where('isCancelled', false);
            } elseif ($request->deliveryStatus === 'pending') {
                $q->whereNull('deliveredAt')->where('isCancelled', false);
            }
        }
        $sales = $q->paginate(20)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        $saleIds = $sales->getCollection()->pluck('id')->values()->all();
        return view('sales.index', compact('sales', 'customers', 'saleIds'));
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
        $products = Product::where('isActive', true)->orderBy('name')->get();
        $personnel = Personnel::where('isActive', true)->orderBy('name')->get();
        return view('sales.edit', compact('sale', 'customers', 'products', 'personnel'));
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
                'kdvRate' => (float) ($item['kdvRate'] ?? 18),
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
            $this->saleService->updateSaleItems($sale, $items, [
                'saleDiscountPercent' => (float) ($validated['saleDiscountPercent'] ?? 0),
                'grandTotalOverride' => isset($validated['grandTotalOverride']) && $validated['grandTotalOverride'] > 0
                    ? (float) $validated['grandTotalOverride'] : null,
            ]);
            $this->auditService->logUpdate('sale', $sale->id, [], ['saleNumber' => $sale->saleNumber]);
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
        $saleRemaining = max(0, (float) $sale->grandTotal - (float) ($sale->paidAmount ?? 0));
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        return view('sales.show', compact('sale', 'unlinkedPayments', 'saleRemaining', 'kasalar'));
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

        return view('sales.workshop', compact('sale', 'variant'));
    }

    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş siparişin durumu güncellenemez.');
        }

        $validated = $request->validate([
            'deliveryStatus' => 'required|in:pending,delivered',
        ]);

        if ($validated['deliveryStatus'] === 'delivered') {
            if (!$sale->deliveredAt) {
                $sale->update(['deliveredAt' => now()]);
            }
            $message = 'Sipariş teslim edildi olarak işaretlendi.';
        } else {
            if ($sale->deliveredAt) {
                $sale->update(['deliveredAt' => null]);
            }
            $message = 'Sipariş teslim bekliyor olarak güncellendi.';
        }

        return redirect()->route('sales.show', $sale)->with('success', $message);
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
        $filename = 'sevkiyat-' . preg_replace('/[^a-zA-Z0-9\-]/', '-', $sale->saleNumber) . '.pdf';
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
        $filename = 'siparis-' . preg_replace('/[^a-zA-Z0-9\-]/', '-', $sale->saleNumber) . '.pdf';
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
            $this->reverseSaleStock($saleId, $saleNumber, 'satis_silme');
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
        $this->auditService->logCancel('sale', $sale->id);
        return redirect()->route('sales.show', $sale)->with('success', 'Satış iptal edildi.');
    }

    public function markDelivered(Sale $sale)
    {
        if ($sale->isCancelled) {
            return redirect()->route('sales.show', $sale)->with('error', 'İptal edilmiş satış teslim edildi olarak işaretlenemez.');
        }
        if ($sale->deliveredAt) {
            return redirect()->route('sales.show', $sale)->with('info', 'Bu satış zaten teslim edildi olarak işaretli.');
        }
        $sale->update(['deliveredAt' => now()]);
        return redirect()->route('sales.show', $sale)->with('success', 'Satış teslim edildi olarak işaretlendi.');
    }

    public function unmarkDelivered(Sale $sale)
    {
        if (!$sale->deliveredAt) {
            return redirect()->route('sales.show', $sale)->with('info', 'Bu satış teslim edildi olarak işaretli değil.');
        }
        $sale->update(['deliveredAt' => null]);
        return redirect()->route('sales.show', $sale)->with('success', 'Teslim işareti kaldırıldı.');
    }

    /** Satış iptal/silme: Bu satıştan yapılan stok çıkışlarını depoya iade eder. */
    private function reverseSaleStock(string $saleId, string $saleNumber, string $refType): void
    {
        $movements = StockMovement::where('refType', 'satis')->where('refId', $saleId)->get();
        foreach ($movements as $m) {
            $qty = (int) abs($m->quantity);
            if ($qty > 0 && $m->productId && $m->warehouseId) {
                $this->stockService->movement(
                    $m->productId,
                    $m->warehouseId,
                    'giris',
                    $qty,
                    [
                        'refType' => $refType,
                        'refId' => $saleId,
                        'description' => "Stok iade - {$refType}: {$saleNumber}",
                    ]
                );
            }
        }
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
                $paymentTypeLabel = match ($paymentType) {
                    'nakit' => 'Nakit',
                    'havale' => 'Havale',
                    'kredi_karti' => 'Kredi Kartı',
                    default => '',
                };
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
}
