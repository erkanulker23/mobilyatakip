<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesTurkeyAddress;
use App\Models\Branch;
use App\Services\AuditService;
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

        $branches = $q->withCount(['sales', 'serviceTickets'])->paginate(20)->withQueryString();

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
        $branch->loadCount(['sales', 'serviceTickets']);
        $recentSales = $branch->sales()->with('customer:id,name')->orderByDesc('createdAt')->limit(10)->get();
        $recentTickets = $branch->serviceTickets()->with('customer:id,name')->orderByDesc('createdAt')->limit(10)->get();

        return view('branches.show', compact('branch', 'recentSales', 'recentTickets'));
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
        $ticketCount = $branch->serviceTickets()->count();
        if ($salesCount > 0 || $ticketCount > 0) {
            return redirect()->route('branches.show', $branch)->with(
                'error',
                'Bu şubeye bağlı '.($salesCount + $ticketCount).' kayıt var. Silmek yerine şubeyi pasif yapın veya kayıtları başka şubeye taşıyın.'
            );
        }

        $this->auditService->logDelete('branch', $branch->id, ['name' => $branch->name]);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Şube silindi.');
    }
}
