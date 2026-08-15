<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesTurkeyAddress;
use App\Models\Branch;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ValidatesTurkeyAddress;

    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = Branch::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $q->where('isActive', $request->status === 'active');
        }

        $branches = $q->withCount(['sales', 'quotes', 'serviceTickets', 'personnel'])->paginate(20)->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code',
            'phone' => 'nullable|string|max:50',
        ]);
        $validated['code'] = filled($validated['code'] ?? null) ? trim((string) $validated['code']) : null;
        $validated['phone'] = filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null;
        $validated['isActive'] = true;
        $branch = Branch::create($validated);
        $this->auditService->logCreate('branch', $branch->id, ['name' => $branch->name]);

        return redirect()->route('branches.index')->with('success', 'Şube kaydedildi.');
    }

    public function show(Branch $branch)
    {
        $branch->loadCount(['sales', 'quotes', 'serviceTickets', 'personnel']);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthStats = $this->branchPeriodStats($branch, $thisMonthStart, $thisMonthEnd);
        $lastMonthStats = $this->branchPeriodStats($branch, $lastMonthStart, $lastMonthEnd);

        $allTimeSalesQuery = $branch->sales()->where('isCancelled', false);
        $salesStats = (object) [
            'total' => (float) (clone $allTimeSalesQuery)->sum('grandTotal'),
            'receivable' => (float) (clone $allTimeSalesQuery)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
                ->value('receivable'),
            'activeCount' => (int) (clone $allTimeSalesQuery)->count(),
        ];

        $terminHorizon = Carbon::today()->addDays(7);
        $upcomingDueSales = $branch->sales()
            ->with('customer:id,name,phone')
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->orderBy('dueDate')
            ->limit(12)
            ->get();

        $recentSales = $branch->sales()
            ->with('customer:id,name')
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->limit(10)
            ->get();

        $recentQuotes = $branch->quotes()
            ->with('customer:id,name')
            ->orderByDesc('createdAt')
            ->limit(10)
            ->get();

        $recentTickets = $branch->serviceTickets()
            ->with('customer:id,name')
            ->orderByDesc('createdAt')
            ->limit(10)
            ->get();

        $branchPersonnel = $branch->personnel()
            ->orderByDesc('isActive')
            ->orderBy('name')
            ->get(['id', 'name', 'title', 'category', 'isActive', 'photoUrl', 'phone']);

        $activePersonnelCount = $branchPersonnel->where('isActive', true)->count();

        $monthlyPerformance = [
            'thisMonth' => [
                'label' => $thisMonthStart->locale('tr')->isoFormat('MMMM YYYY'),
                ...$thisMonthStats,
            ],
            'lastMonth' => [
                'label' => $lastMonthStart->locale('tr')->isoFormat('MMMM YYYY'),
                ...$lastMonthStats,
            ],
            'countChange' => $this->periodChangePercent($thisMonthStats['count'], $lastMonthStats['count']),
            'totalChange' => $this->periodChangePercent($thisMonthStats['total'], $lastMonthStats['total']),
        ];

        return view('branches.show', compact(
            'branch',
            'recentSales',
            'recentQuotes',
            'recentTickets',
            'branchPersonnel',
            'salesStats',
            'monthlyPerformance',
            'upcomingDueSales',
            'activePersonnelCount',
        ));
    }

    /** @return array{count: int, total: float, receivable: float} */
    private function branchPeriodStats(Branch $branch, Carbon $start, Carbon $end): array
    {
        $base = $branch->sales()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$start->toDateString(), $end->toDateString()]);

        return [
            'count' => (int) (clone $base)->count(),
            'total' => (float) (clone $base)->sum('grandTotal'),
            'receivable' => (float) (clone $base)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
                ->value('receivable'),
        ];
    }

    private function periodChangePercent(float|int $current, float|int $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous <= 0.005) {
            return $current > 0.005 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code,'.$branch->id,
            'phone' => 'nullable|string|max:50',
            'isActive' => 'boolean',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        $validated['code'] = filled($validated['code'] ?? null) ? trim((string) $validated['code']) : null;
        $validated['phone'] = filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null;
        $oldData = ['name' => $branch->name];
        $branch->update($validated);
        $this->auditService->logUpdate('branch', $branch->id, $oldData, ['name' => $branch->name]);

        return redirect()->route('branches.show', $branch)->with('success', 'Şube güncellendi.');
    }

    public function destroy(Branch $branch)
    {
        $salesCount = $branch->sales()->count();
        $quoteCount = $branch->quotes()->count();
        $ticketCount = $branch->serviceTickets()->count();
        $personnelCount = $branch->personnel()->count();
        if ($salesCount > 0 || $quoteCount > 0 || $ticketCount > 0 || $personnelCount > 0) {
            return redirect()->route('branches.show', $branch)->with(
                'error',
                'Bu şubeye bağlı '.($salesCount + $quoteCount + $ticketCount + $personnelCount).' kayıt var. Silmek yerine şubeyi pasif yapın veya kayıtları başka şubeye taşıyın.'
            );
        }

        $this->auditService->logDelete('branch', $branch->id, ['name' => $branch->name]);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Şube silindi.');
    }
}
