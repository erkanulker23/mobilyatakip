<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Personnel;
use App\Models\PersonnelAdvance;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PersonnelAdvanceController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function create()
    {
        $personnel = Personnel::query()->where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();

        return view('personnel-advances.create', compact('personnel', 'kasalar'));
    }

    public function store(Request $request)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $validated = $request->validate([
            'personnelId' => 'required|exists:personnel,id',
            'amount' => 'required|numeric|min:0.01',
            'advanceDate' => 'required|date',
            'period' => 'required|in:gunluk,aylik',
            'kasaId' => 'required|exists:kasa,id',
            'note' => 'nullable|string|max:500',
        ]);

        $validated['createdBy'] = auth()->id() ?: null;

        if (! \Illuminate\Support\Facades\Schema::hasTable('personnel_advances')) {
            return back()->withInput()->with('error', 'Avans tablosu yok. Sunucuda veritabanı güncellemesi çalıştırılmalı.');
        }

        $advance = PersonnelAdvance::create($validated);
        $advance->load('personnel');

        KasaHareket::create([
            'kasaId' => $validated['kasaId'],
            'type' => 'cikis',
            'amount' => -(float) $validated['amount'],
            'movementDate' => $validated['advanceDate'],
            'description' => $advance->cariLabel().($advance->note ? ' · '.$advance->note : ''),
            'createdBy' => auth()->id() ?: null,
            'refType' => 'personnel_advance',
            'refId' => $advance->id,
        ]);

        $this->auditService->logCreate('personnel_advance', $advance->id, [
            'personnelId' => $advance->personnelId,
            'amount' => (float) $advance->amount,
            'period' => $advance->period,
        ]);

        return redirect()->route('personnel.show', $advance->personnelId)
            ->with('success', 'Personel avansı carisine işlendi.');
    }

    public function destroy(PersonnelAdvance $personnelAdvance)
    {
        KasaHareket::where('refType', 'personnel_advance')->where('refId', $personnelAdvance->id)->delete();
        $personnelId = $personnelAdvance->personnelId;
        $this->auditService->logDelete('personnel_advance', $personnelAdvance->id, [
            'amount' => (float) $personnelAdvance->amount,
        ]);
        $personnelAdvance->delete();

        return redirect()->back(fallback: route('personnel.show', $personnelId))
            ->with('success', 'Personel avansı silindi.');
    }
}
