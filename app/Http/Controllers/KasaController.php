<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use Illuminate\Http\Request;

class KasaController extends Controller
{
    public function index(Request $request)
    {
        $q = Kasa::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('bankName', 'like', "%{$s}%")
                    ->orWhere('iban', 'like', "%{$s}%")
                    ->orWhere('accountNumber', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
        $kasalar = $q->paginate(20)->withQueryString();
        return view('kasa.index', compact('kasalar'));
    }

    public function show(Request $request, Kasa $kasa)
    {
        $hareketler = $kasa->hareketler()
            ->orderBy('movementDate', 'desc')
            ->orderBy('createdAt', 'desc')
            ->paginate(20)
            ->withQueryString();
        return view('kasa.show', compact('kasa', 'hareketler'));
    }

    public function create()
    {
        return view('kasa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:kasa,banka',
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
        ]);
        $validated['type'] = $validated['type'] ?? 'kasa';
        $validated['openingBalance'] = $validated['openingBalance'] ?? 0;
        $validated['currency'] = $validated['currency'] ?? 'TRY';
        Kasa::create($validated);
        return redirect()->route('kasa.index')->with('success', 'Kasa kaydedildi.');
    }

    public function edit(Kasa $kasa)
    {
        return view('kasa.edit', compact('kasa'));
    }

    public function update(Request $request, Kasa $kasa)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:kasa,banka',
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['type'] = $validated['type'] ?? 'kasa';
        $validated['openingBalance'] = $validated['openingBalance'] ?? 0;
        $validated['isActive'] = $request->boolean('isActive');
        $kasa->update($validated);
        return redirect()->route('kasa.index')->with('success', 'Kasa güncellendi.');
    }
}
