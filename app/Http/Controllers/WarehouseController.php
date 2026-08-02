<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesTurkeyAddress;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use ValidatesTurkeyAddress;

    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = Warehouse::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        $warehouses = $q->paginate(20)->withQueryString();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
        ]);
        $warehouse = Warehouse::create($validated);
        $this->auditService->logCreate('warehouse', $warehouse->id, ['name' => $warehouse->name]);
        return redirect()->route('warehouses.index')->with('success', 'Depo kaydedildi.');
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('stocks.product');
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'isActive' => 'boolean',
        ]);
        $oldData = ['name' => $warehouse->name];
        $warehouse->update($validated);
        $this->auditService->logUpdate('warehouse', $warehouse->id, $oldData, ['name' => $warehouse->name]);
        return redirect()->route('warehouses.index')->with('success', 'Depo güncellendi.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->auditService->logDelete('warehouse', $warehouse->id, ['name' => $warehouse->name]);
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Depo silindi.');
    }
}
