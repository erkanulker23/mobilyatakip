<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Rules\TurkishTaxId;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
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
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
                'address' => 'nullable|string',
            ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);
        return response()->json(['id' => $customer->id, 'name' => $customer->name]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'identityNumber' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]+$/', new TurkishTaxId('tckn')],
            'taxNumber' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'identityNumber.size' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'identityNumber.regex' => 'TC kimlik numarası sadece rakamlardan oluşmalıdır.',
            'taxNumber.size' => 'Vergi numarası 10 haneli olmalıdır.',
        ]);
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri kaydedildi.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments.sale']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['sales', 'quotes']);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'identityNumber' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]+$/', new TurkishTaxId('tckn')],
            'taxNumber' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'boolean',
        ], [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'identityNumber.size' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'identityNumber.regex' => 'TC kimlik numarası sadece rakamlardan oluşmalıdır.',
            'taxNumber.size' => 'Vergi numarası 10 haneli olmalıdır.',
        ]);
        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri güncellendi.');
    }

    public function print(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments']);
        $totalSales = $customer->sales->sum('grandTotal');
        $totalPaid = $customer->payments->sum('amount');
        $totalDebt = $totalSales - $totalPaid;
        return view('customers.print', compact('customer', 'totalSales', 'totalPaid', 'totalDebt'));
    }

    public function destroy(Customer $customer)
    {
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
        try {
            Excel::import(new CustomersImport, $request->file('file'));
            return redirect()->route('customers.index')->with('success', 'Excel dosyası başarıyla içe aktarıldı.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $msg = collect($e->failures())->map(fn ($f) => 'Satır ' . $f->row() . ': ' . implode(', ', $f->errors()))->implode('; ');
            return redirect()->route('customers.index')->with('error', 'İçe aktarma hatası: ' . $msg);
        }
    }
}
