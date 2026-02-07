<?php

namespace App\Http\Controllers;

use App\Exports\SuppliersExport;
use App\Imports\SuppliersImport;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('taxNumber', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $suppliers = $q->paginate(20)->withQueryString();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi kaydedildi.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases.items.product', 'products', 'payments.purchase']);
        return view('suppliers.show', compact('supplier'));
    }

    public function print(Supplier $supplier)
    {
        $supplier->load(['purchases.items.product', 'payments']);
        return view('suppliers.print', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        $validated['isActive'] = $request->boolean('isActive');
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi güncellendi.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi silindi.');
    }

    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(new SuppliersExport, 'tedarikciler-' . date('Y-m-d') . '.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new SuppliersImport, $request->file('file'));
            return redirect()->route('suppliers.index')->with('success', 'Excel dosyası başarıyla içe aktarıldı.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $msg = collect($e->failures())->map(fn ($f) => 'Satır ' . $f->row() . ': ' . implode(', ', $f->errors()))->implode('; ');
            return redirect()->route('suppliers.index')->with('error', 'İçe aktarma hatası: ' . $msg);
        }
    }
}
