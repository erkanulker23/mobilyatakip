<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use Illuminate\Http\Request;

class KasaController extends Controller
{
    public function index()
    {
        $kasalar = Kasa::orderBy('name')->paginate(20);
        return view('kasa.index', compact('kasalar'));
    }

    public function show(Kasa $kasa)
    {
        $kasa->load('hareketler');
        return view('kasa.show', compact('kasa'));
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
        ]);
        $validated['type'] = $validated['type'] ?? 'kasa';
        $validated['openingBalance'] = $validated['openingBalance'] ?? 0;
        Kasa::create($validated);
        return redirect()->route('kasa.index')->with('success', 'Kasa kaydedildi.');
    }
}
