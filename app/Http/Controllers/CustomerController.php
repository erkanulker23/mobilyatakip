<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Http\Controllers\Concerns\ValidatesTurkeyAddress;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use App\Rules\TurkishTaxId;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    use ValidatesTurkeyAddress;

    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = Customer::query()
            ->with(['city', 'district'])
            ->withSum(['sales as totalSales' => fn ($s) => $s->where('isCancelled', false)], 'grandTotal')
            ->withSum('payments as totalPaid', 'amount')
            ->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('phone2', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%")
                    ->orWhere('taxNumber', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $customers = $q->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function quickStore(Request $request)
    {
        try {
            $validated = $request->validate(array_merge([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
                'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            ], \App\Support\AddressFormat::validationRules()), [
                'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
                'phone2.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }
        if ($message = \App\Support\AddressFormat::assertDistrictMatchesCity($validated)) {
            return response()->json(['message' => $message], 422);
        }
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'phone2' => $validated['phone2'] ?? null,
            'address' => $validated['address'] ?? null,
            'cityId' => $validated['cityId'] ?? null,
            'districtId' => $validated['districtId'] ?? null,
        ]);
        $customer->load(['city', 'district']);
        $this->auditService->logCreate('customer', $customer->id, ['name' => $customer->name]);
        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'address' => $customer->full_address,
            'cityId' => $customer->cityId,
            'districtId' => $customer->districtId,
        ]);
    }

    public function salesJson(Customer $customer)
    {
        $sales = Sale::query()
            ->where('customerId', $customer->id)
            ->where('isCancelled', false)
            ->orderBy('saleDate', 'desc')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'customerId' => $s->customerId,
                'label' => $s->saleNumber . ' · ' . ($s->saleDate?->format('d.m.Y') ?? '—') . ' · ' . number_format((float) $s->grandTotal, 0, ',', '.') . ' ₺',
            ])
            ->values();

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'identityNumber' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]+$/', new TurkishTaxId('tckn')],
            'taxNumber' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'phone2.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'identityNumber.size' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'identityNumber.regex' => 'TC kimlik numarası sadece rakamlardan oluşmalıdır.',
            'taxNumber.size' => 'Vergi numarası 10 haneli olmalıdır.',
        ]);
        $customer = Customer::create($validated);
        $this->auditService->logCreate('customer', $customer->id, ['name' => $customer->name]);
        return redirect()->route('customers.index')->with('success', 'Müşteri kaydedildi.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments.sale', 'serviceTickets.sale', 'city', 'district']);

        $serviceTickets = \App\Models\ServiceTicket::query()
            ->with('sale')
            ->where(function ($q) use ($customer) {
                $q->where('customerId', $customer->id)
                    ->orWhereIn('saleId', $customer->sales->pluck('id'));
            })
            ->orderByDesc('createdAt')
            ->get();

        return view('customers.show', compact('customer', 'serviceTickets'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['sales', 'quotes']);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validateWithTurkeyAddress($request, [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'identityNumber' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]+$/', new TurkishTaxId('tckn')],
            'taxNumber' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'boolean',
        ], [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'phone2.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'identityNumber.size' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'identityNumber.regex' => 'TC kimlik numarası sadece rakamlardan oluşmalıdır.',
            'taxNumber.size' => 'Vergi numarası 10 haneli olmalıdır.',
        ]);
        $oldData = ['name' => $customer->name];
        $customer->update($validated);
        $this->auditService->logUpdate('customer', $customer->id, $oldData, ['name' => $customer->name]);
        return redirect()->route('customers.index')->with('success', 'Müşteri güncellendi.');
    }

    public function print(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments', 'city', 'district']);
        $totalSales = $customer->sales->where('isCancelled', false)->sum('grandTotal');
        $totalPaid = $customer->payments->sum('amount');
        $customerBalance = \App\Support\CustomerBalance::customerStatus((float) $totalSales, (float) $totalPaid);
        return view('customers.print', compact('customer', 'totalSales', 'totalPaid', 'customerBalance'));
    }

    public function destroy(Customer $customer)
    {
        $id = $customer->getKey();
        $quoteIds = DB::table('quotes')->where('customerId', $id)->pluck('id');
        if ($quoteIds->isNotEmpty()) {
            DB::table('sales')->whereIn('quoteId', $quoteIds)->update(['quoteId' => null]);
            DB::table('quote_items')->whereIn('quoteId', $quoteIds)->delete();
            DB::table('quotes')->where('customerId', $id)->delete();
        }
        DB::table('customer_payments')->where('customerId', $id)->delete();
        $saleIds = DB::table('sales')->where('customerId', $id)->pluck('id');
        if ($saleIds->isNotEmpty()) {
            DB::table('sale_activities')->whereIn('saleId', $saleIds)->delete();
            DB::table('sale_items')->whereIn('saleId', $saleIds)->delete();
            $ticketIds = DB::table('service_tickets')->whereIn('saleId', $saleIds)->pluck('id');
            if ($ticketIds->isNotEmpty()) {
                $detailIds = DB::table('service_ticket_details')->whereIn('ticketId', $ticketIds)->pluck('id');
                if ($detailIds->isNotEmpty()) {
                    DB::table('service_parts')->whereIn('detailId', $detailIds)->delete();
                }
                DB::table('service_ticket_details')->whereIn('ticketId', $ticketIds)->delete();
            }
            DB::table('service_tickets')->whereIn('saleId', $saleIds)->delete();
            DB::table('sales')->where('customerId', $id)->delete();
        }
        DB::table('service_tickets')->where('customerId', $id)->update(['customerId' => null]);
        $this->auditService->logDelete('customer', $customer->id, ['name' => $customer->name]);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Müşteri silindi.');
    }

    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(new CustomersExport, 'musteriler-' . date('Y-m-d') . '.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        $import = new CustomersImport;
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('customers.index')->with('error', 'İçe aktarma hatası: ' . $e->getMessage());
        }
        $msg = "{$import->created} müşteri eklendi, {$import->updated} müşteri güncellendi.";
        if ($import->skipped > 0) {
            $msg .= " {$import->skipped} satır atlandı.";
        }
        if ($import->errors !== []) {
            $msg .= ' ' . implode(' ', array_slice($import->errors, 0, 5));
        }
        return redirect()->route('customers.index')->with('success', $msg);
    }
}
