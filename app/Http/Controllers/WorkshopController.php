<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\SaleProductionStage;
use App\Support\SaleDelivery;
use App\Support\SaleProductionStageSchema;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function index(Request $request)
    {
        $q = Sale::query()
            ->with(['customer', 'personnel'])
            ->where('isCancelled', false)
            ->where('orderStatus', SaleDelivery::IN_PRODUCTION)
            ->whereNull('deliveredAt')
            ->orderByRaw('CASE WHEN dueDate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('dueDate')
            ->orderByDesc('saleDate');

        SaleProductionStageSchema::applyCounts($q, detailed: true);

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('saleNumber', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            });
        }

        $sales = $q->paginate(20)->withQueryString();

        return view('workshop.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $this->authorizeProductionSale($sale);

        $sale->load([
            'customer.city',
            'customer.district',
            'personnel',
            'items.product',
        ]);

        if (SaleProductionStageSchema::isReady()) {
            $sale->load([
                'productionStages.user',
                'productionStages.completedByUser',
            ]);
        } else {
            $sale->setRelation('productionStages', collect());
        }

        $backUrl = auth()->user()?->isWorkshop() && auth()->user()?->personnel
            ? route('personnel.show', auth()->user()->personnel)
            : route('workshop.index');

        $productionStagesReady = SaleProductionStageSchema::isReady();

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
        ));
    }

    public function storeStage(Request $request, Sale $sale)
    {
        SaleProductionStageSchema::abortIfNotReady();

        $this->authorizeProductionSale($sale);

        $validated = $request->validate([
            'type' => 'required|in:asama,eksiklik',
            'notes' => 'required|string|max:2000',
        ]);

        SaleProductionStage::create([
            'saleId' => $sale->id,
            'userId' => $request->user()?->id,
            'type' => $validated['type'],
            'notes' => $validated['notes'],
            'actionDate' => now(),
        ]);

        $label = SaleProductionStage::typeLabel($validated['type']);

        return redirect()
            ->route('workshop.show', $sale)
            ->with('success', "{$label} kaydı eklendi.");
    }

    public function completeStage(Request $request, SaleProductionStage $stage)
    {
        SaleProductionStageSchema::abortIfNotReady();

        $stage->load('sale');
        $this->authorizeProductionSale($stage->sale);

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

        $redirect = auth()->user()?->isWorkshop() && auth()->user()?->personnel
            ? route('personnel.show', auth()->user()->personnel)
            : route('workshop.index');

        return redirect($redirect)->with('success', 'Sipariş atölyeden çıktı. Durum: Teslim bekliyor.');
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
