<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\SaleProductionStage;
use App\Support\SaleDelivery;
use App\Support\SaleProductionStageSchema;
use App\Support\WorkshopDashboard;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function dashboard(Request $request)
    {
        $personnel = $request->user()?->personnel;
        if (! $personnel || ! $request->user()?->isWorkshop()) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return view('workshop.dashboard', WorkshopDashboard::viewData($personnel));
    }

    public function index(Request $request)
    {
        $scope = $request->input('scope', 'uretim');
        if (! in_array($scope, ['uretim', 'tumu', 'tamamlanan'], true)) {
            $scope = 'uretim';
        }

        $relations = [
            'customer.city',
            'customer.district',
            'personnel',
            'items.product',
        ];

        if (SaleProductionStageSchema::isReady()) {
            $relations['productionStages'] = fn ($q) => $q->with(['user', 'completedByUser', 'saleItem.product']);
        }

        $q = Sale::query()
            ->with($relations)
            ->where('isCancelled', false);

        if (SaleProductionStageSchema::isReady()) {
            SaleProductionStageSchema::applyCounts($q, detailed: true);
        }

        match ($scope) {
            'tamamlanan' => $q->whereNotNull('workshopCompletedAt')
                ->orderByDesc('workshopCompletedAt'),
            'tumu' => $q->where(function ($w) {
                $w->where('orderStatus', SaleDelivery::IN_PRODUCTION)
                    ->orWhereNotNull('workshopCompletedAt');
                if (SaleProductionStageSchema::isReady()) {
                    $w->orWhereHas('productionStages');
                }
            })
                ->orderByRaw('CASE WHEN orderStatus = ? AND deliveredAt IS NULL THEN 0 ELSE 1 END', [SaleDelivery::IN_PRODUCTION])
                ->orderByDesc('workshopCompletedAt')
                ->orderByRaw('CASE WHEN dueDate IS NULL THEN 1 ELSE 0 END')
                ->orderBy('dueDate'),
            default => $q->where('orderStatus', SaleDelivery::IN_PRODUCTION)
                ->whereNull('deliveredAt')
                ->orderByRaw('CASE WHEN dueDate IS NULL THEN 1 ELSE 0 END')
                ->orderBy('dueDate')
                ->orderByDesc('saleDate'),
        };

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('saleNumber', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
                if (SaleProductionStageSchema::isReady()) {
                    $w->orWhereHas('productionStages', fn ($st) => $st->where('notes', 'like', "%{$s}%"));
                }
            });
        }

        if ($request->filled('type') && SaleProductionStageSchema::isReady()) {
            $type = $request->type;
            if (in_array($type, [SaleProductionStage::TYPE_STAGE, SaleProductionStage::TYPE_DEFICIENCY], true)) {
                $q->whereHas('productionStages', fn ($st) => $st->where('type', $type));
            }
        }

        $sales = $q->paginate(10)->withQueryString();
        $productionStagesReady = SaleProductionStageSchema::isReady();

        return view('workshop.index', compact('sales', 'scope', 'productionStagesReady'));
    }

    public function show(Request $request, Sale $sale)
    {
        $this->authorizeWorkshopSaleView($sale);

        $sale->load([
            'customer.city',
            'customer.district',
            'personnel',
            'items.product.supplier',
        ]);

        if (SaleProductionStageSchema::isReady()) {
            $sale->load([
                'productionStages.user',
                'productionStages.completedByUser',
                'productionStages.saleItem.product',
            ]);
        } else {
            $sale->setRelation('productionStages', collect());
        }

        $backUrl = match ($request->query('from')) {
            'termin' => route('reports.upcoming-due', ['days' => max(1, min(90, (int) $request->query('days', 14)))]),
            default => auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin()
                ? route('workshop.dashboard')
                : route('workshop.index'),
        };

        $productionStagesReady = SaleProductionStageSchema::isReady();
        $orderStatus = SaleDelivery::currentStatus($sale);
        $canAddProductionStage = ! $sale->isCancelled;
        $canEditProduction = $orderStatus === SaleDelivery::IN_PRODUCTION
            && (auth()->user()?->isAdmin() || auth()->user()?->isWorkshop());

        $openDeficienciesCount = $productionStagesReady
            ? $sale->productionStages
                ->where('type', SaleProductionStage::TYPE_DEFICIENCY)
                ->where('isCompleted', false)
                ->count()
            : 0;

        return view('workshop.show', compact(
            'sale',
            'backUrl',
            'productionStagesReady',
            'openDeficienciesCount',
            'canAddProductionStage',
            'canEditProduction',
            'orderStatus',
        ));
    }

    public function storeStage(Request $request, Sale $sale)
    {
        SaleProductionStageSchema::abortIfNotReady();

        $this->authorizeStageMutation($sale);

        $validated = $request->validate([
            'type' => 'required|in:asama,eksiklik',
            'notes' => 'required|string|max:2000',
            'saleItemId' => 'nullable|string|exists:sale_items,id',
        ]);

        if (! empty($validated['saleItemId'])) {
            $belongsToSale = $sale->items()->where('id', $validated['saleItemId'])->exists();
            if (! $belongsToSale) {
                return back()->withInput()->withErrors(['saleItemId' => 'Seçilen ürün bu siparişe ait değil.']);
            }
        }

        SaleProductionStage::create([
            'saleId' => $sale->id,
            'saleItemId' => $validated['saleItemId'] ?? null,
            'userId' => $request->user()?->id,
            'type' => $validated['type'],
            'notes' => $validated['notes'],
            'actionDate' => now(),
        ]);

        $label = SaleProductionStage::typeLabel($validated['type']);

        return back()->with('success', "{$label} kaydı eklendi.");
    }

    public function completeStage(Request $request, SaleProductionStage $stage)
    {
        SaleProductionStageSchema::abortIfNotReady();

        $stage->load('sale');
        $this->authorizeStageMutation($stage->sale);

        if ($stage->isCompleted) {
            return back()->with('info', 'Bu kayıt zaten tamamlanmış.');
        }

        $stage->update([
            'isCompleted' => true,
            'completedAt' => now(),
            'completedByUserId' => $request->user()?->id,
        ]);

        return back()->with('success', 'Kayıt yapıldı olarak işaretlendi.');
    }

    public function completeProduction(Request $request, Sale $sale)
    {
        $this->authorizeProductionSale($sale);

        $fromStatus = SaleDelivery::currentStatus($sale);

        $sale->update([
            'orderStatus' => SaleDelivery::PENDING,
            'workshopCompletedAt' => now(),
        ]);

        SaleActivity::logWorkshopCompleted($sale->fresh(), $fromStatus, $request->user());

        $redirect = auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin()
            ? route('workshop.dashboard')
            : route('workshop.index');

        return redirect($redirect)->with('success', 'Sipariş atölyeden çıktı. Durum: Teslim bekliyor.');
    }

    private function authorizeWorkshopSaleView(Sale $sale): void
    {
        if ($sale->isCancelled) {
            abort(404);
        }

        if (auth()->user()?->isAdmin()) {
            return;
        }

        if (! auth()->user()?->isWorkshop()) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        if (SaleDelivery::isDelivered($sale)) {
            abort(403, 'Teslim edilmiş sipariş görüntülenemez.');
        }
    }

    private function authorizeStageMutation(Sale $sale): void
    {
        if ($sale->isCancelled) {
            abort(404);
        }
    }

    private function authorizeProductionSale(Sale $sale): void
    {
        if ($sale->isCancelled) {
            abort(404);
        }

        $status = SaleDelivery::currentStatus($sale);

        if (auth()->user()?->isAdmin()) {
            return;
        }

        if ($status !== SaleDelivery::IN_PRODUCTION) {
            abort(403, 'Bu sipariş üretimde değil.');
        }
    }
}
