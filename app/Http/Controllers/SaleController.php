<?php

namespace App\Http\Controllers;

use App\Mail\SaleNotificationToSupplier;
use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\SaleService;
use App\Services\StockService;
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
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $products = Product::where('isActive', true)->orderBy('name')->get();
        return view('sales.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'saleDate' => 'required|date',
            'dueDate' => 'nullable|date',
            'kdvIncluded' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'nullable|string',
            'items.*.productName' => 'nullable|string|max:255',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kdvRate' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountPercent' => 'nullable|numeric|min:0|max:100',
            'items.*.lineDiscountAmount' => 'nullable|numeric|min:0',
        ]);
        $items = collect($validated['items'])->map(function ($item) {
            $product = !empty($item['productId']) ? \App\Models\Product::find($item['productId']) : null;
            if ($product) {
                return ['productId' => $product->id, 'productName' => null, 'unitPrice' => $item['unitPrice'], 'quantity' => $item['quantity'], 'kdvRate' => $item['kdvRate'] ?? 18, 'lineDiscountPercent' => $item['lineDiscountPercent'] ?? null, 'lineDiscountAmount' => $item['lineDiscountAmount'] ?? null];
            }
            $name = trim($item['productName'] ?? '') ?: trim($item['productId'] ?? '');
            return ['productId' => null, 'productName' => $name, 'unitPrice' => $item['unitPrice'], 'quantity' => $item['quantity'], 'kdvRate' => $item['kdvRate'] ?? 18, 'lineDiscountPercent' => $item['lineDiscountPercent'] ?? null, 'lineDiscountAmount' => $item['lineDiscountAmount'] ?? null];
        })->filter(fn($i) => !empty($i['productId']) || !empty($i['productName']))->values()->all();
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'En az bir geçerli kalem girin (ürün seçin veya manuel ürün adı yazın).');
        }
        try {
            $sale = $this->saleService->createDirect([
                'customerId' => $validated['customerId'],
                'saleDate' => $validated['saleDate'],
                'dueDate' => $validated['dueDate'] ?? null,
                'kdvIncluded' => $request->boolean('kdvIncluded'),
                'notes' => $validated['notes'] ?? null,
                'items' => $items,
            ]);
            $this->auditService->logCreate('sale', $sale->id, ['saleNumber' => $sale->saleNumber, 'grandTotal' => $sale->grandTotal]);
            return redirect()->route('sales.show', $sale)
                ->with('success', 'Satış oluşturuldu.')
                ->with('show_supplier_email_prompt', true);
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
        return view('sales.show', compact('sale', 'unlinkedPayments'));
    }

    public function print(Sale $sale)
    {
        $sale = $this->saleService->find($sale->id);
        if (!$sale) {
            abort(404);
        }
        return view('sales.print', compact('sale'));
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
}
