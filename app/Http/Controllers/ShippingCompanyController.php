<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesTurkeyAddress;
use App\Models\Purchase;
use App\Models\ServiceTicket;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyPayment;
use App\Models\ShippingCompanyVehicle;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ShippingCompanyController extends Controller
{
    use ValidatesTurkeyAddress;

    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = ShippingCompany::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $shippingCompanies = $q->paginate(20)->withQueryString();
        $ids = $shippingCompanies->getCollection()->pluck('id')->values()->all();

        $odemeByShipping = [];
        if (!empty($ids)) {
            $odemeByShipping = ShippingCompanyPayment::whereIn('shippingCompanyId', $ids)
                ->selectRaw('shippingCompanyId, sum(amount) as total')
                ->groupBy('shippingCompanyId')
                ->pluck('total', 'shippingCompanyId')
                ->map(fn ($v) => (float) $v)
                ->all();
        }

        return view('shipping-companies.index', compact('shippingCompanies', 'odemeByShipping'));
    }

    public function create()
    {
        return view('shipping-companies.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
        ]);
        $company = ShippingCompany::create($validated);
        $this->auditService->logCreate('shipping_company', $company->id, ['name' => $company->name]);
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması kaydedildi.');
    }

    public function show(ShippingCompany $shippingCompany)
    {
        $shippingCompany->load(['purchases.supplier', 'payments.purchase', 'payments.sale', 'payments.serviceTicket', 'vehicles']);
        return view('shipping-companies.show', compact('shippingCompany'));
    }

    public function edit(ShippingCompany $shippingCompany)
    {
        return view('shipping-companies.edit', compact('shippingCompany'));
    }

    public function update(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        $oldData = ['name' => $shippingCompany->name];
        $shippingCompany->update($validated);
        $this->auditService->logUpdate('shipping_company', $shippingCompany->id, $oldData, ['name' => $shippingCompany->name]);
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması güncellendi.');
    }

    public function destroy(ShippingCompany $shippingCompany)
    {
        $shippingCompany->payments()->delete();
        Purchase::where('shippingCompanyId', $shippingCompany->id)->update(['shippingCompanyId' => null]);
        ServiceTicket::where('shippingCompanyId', $shippingCompany->id)->update([
            'shippingCompanyId' => null,
            'shippingVehicleId' => null,
        ]);
        $shippingCompany->vehicles()->delete();
        $this->auditService->logDelete('shipping_company', $shippingCompany->id, ['name' => $shippingCompany->name]);
        $shippingCompany->delete();
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması silindi.');
    }

    public function storeVehicle(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $request->validate([
            'vehiclePlate' => 'required|string|max:20',
            'driverName' => 'nullable|string|max:100',
            'driverPhone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+][0-9\s\-()]{9,24}$/'],
            'notes' => 'nullable|string|max:500',
        ], [
            'driverPhone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);

        $vehicle = $shippingCompany->vehicles()->create($validated);
        $this->auditService->logCreate('shipping_company_vehicle', $vehicle->id, [
            'shippingCompanyId' => $shippingCompany->id,
            'vehiclePlate' => $vehicle->vehiclePlate,
        ]);

        return back()->with('success', 'Araç eklendi.');
    }

    public function updateVehicle(Request $request, ShippingCompany $shippingCompany, ShippingCompanyVehicle $shippingCompanyVehicle)
    {
        if ($shippingCompanyVehicle->shippingCompanyId !== $shippingCompany->id) {
            abort(404);
        }

        $validated = $request->validate([
            'vehiclePlate' => 'required|string|max:20',
            'driverName' => 'nullable|string|max:100',
            'driverPhone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+][0-9\s\-()]{9,24}$/'],
            'notes' => 'nullable|string|max:500',
            'isActive' => 'nullable|boolean',
        ], [
            'driverPhone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);
        $validated['isActive'] = $request->boolean('isActive', true);

        $shippingCompanyVehicle->update($validated);

        return back()->with('success', 'Araç güncellendi.');
    }

    public function destroyVehicle(ShippingCompany $shippingCompany, ShippingCompanyVehicle $shippingCompanyVehicle)
    {
        if ($shippingCompanyVehicle->shippingCompanyId !== $shippingCompany->id) {
            abort(404);
        }

        ServiceTicket::where('shippingVehicleId', $shippingCompanyVehicle->id)->update(['shippingVehicleId' => null]);
        $shippingCompanyVehicle->delete();

        return back()->with('success', 'Araç silindi.');
    }
}
