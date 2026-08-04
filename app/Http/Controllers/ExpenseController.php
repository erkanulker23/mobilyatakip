<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private AuditService $auditService) {}
    public function index(Request $request)
    {
        $query = Expense::with(['kasa', 'createdByUser'])->orderBy('expenseDate', 'desc');
        if ($request->filled('from')) {
            $query->where('expenseDate', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('expenseDate', '<=', $request->to);
        }
        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('description', 'like', "%{$s}%");
        }
        if ($request->filled('kasaId')) {
            $query->where('kasaId', $request->kasaId);
        }
        $total = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();
        $kasalar = Kasa::orderBy('name')->get();
        return view('expenses.index', compact('expenses', 'total', 'kasalar'));
    }

    private function categoriesForForm(): array
    {
        $defaults = [
            'Kira', 'Elektrik', 'Su', 'Doğalgaz', 'Personel', 'Kırtasiye', 'Vergi', 'Sigorta',
            'Bakım', 'Ulaşım', 'Reklam', 'Müşteri İkram', 'Mutfak Gideri', 'Diğer',
        ];
        $categories = array_unique(array_merge(
            $defaults,
            Expense::distinct()->whereNotNull('category')->where('category', '!=', '')->pluck('category')->toArray()
        ));
        sort($categories);

        return $categories;
    }

    public function create()
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = $this->categoriesForForm();
        return view('expenses.create', compact('kasalar', 'categories'));
    }

    public function store(Request $request)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ]);
        $validated['createdBy'] = auth()->id() ?: null;
        $validated['kdvIncluded'] = true;
        $validated['kdvRate'] = 0;
        $validated['kdvAmount'] = 0;
        $expense = Expense::create($validated);
        if (!empty($validated['kasaId'])) {
            KasaHareket::create([
                'kasaId' => $validated['kasaId'],
                'type' => 'cikis',
                'amount' => -(float) $validated['amount'],
                'movementDate' => $validated['expenseDate'],
                'description' => 'Gider - ' . ($validated['category'] ? $validated['category'] . ': ' : '') . ($validated['description'] ?? ''),
                'createdBy' => auth()->id() ?: null,
                'refType' => 'expense',
                'refId' => $expense->id,
            ]);
        }
        $this->auditService->logCreate('expense', $expense->id, ['amount' => $validated['amount'], 'description' => $validated['description']]);
        return redirect()->route('expenses.show', $expense)->with('success', 'Gider kaydedildi.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['kasa', 'createdByUser']);
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = $this->categoriesForForm();
        return view('expenses.edit', compact('expense', 'kasalar', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ]);
        $validated['kdvIncluded'] = true;
        $validated['kdvRate'] = 0;
        $validated['kdvAmount'] = 0;
        $expense->update($validated);

        $hareket = KasaHareket::where('refType', 'expense')->where('refId', $expense->id)->first();
        if ($hareket) {
            KasaHareket::create([
                'kasaId' => $hareket->kasaId,
                'type' => 'giris',
                'amount' => abs((float) $hareket->amount),
                'movementDate' => $validated['expenseDate'],
                'description' => 'Gider iptal - ' . ($expense->category ? $expense->category . ': ' : '') . $expense->description,
                'createdBy' => auth()->id() ?: null,
            ]);
            $hareket->delete();
        }
        if (!empty($validated['kasaId'])) {
            KasaHareket::create([
                'kasaId' => $validated['kasaId'],
                'type' => 'cikis',
                'amount' => -(float) $validated['amount'],
                'movementDate' => $validated['expenseDate'],
                'description' => 'Gider - ' . ($validated['category'] ? $validated['category'] . ': ' : '') . ($validated['description'] ?? ''),
                'createdBy' => auth()->id() ?: null,
                'refType' => 'expense',
                'refId' => $expense->id,
            ]);
        }

        $this->auditService->logUpdate('expense', $expense->id, [], [
            'amount' => $validated['amount'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('expenses.show', $expense)->with('success', 'Gider güncellendi.');
    }

    public function destroy(Expense $expense)
    {
        $hareket = KasaHareket::where('refType', 'expense')->where('refId', $expense->id)->first();
        if ($hareket) {
            KasaHareket::create([
                'kasaId' => $hareket->kasaId,
                'type' => 'giris',
                'amount' => abs((float) $hareket->amount),
                'movementDate' => now(),
                'description' => 'Gider iptal - ' . ($expense->category ? $expense->category . ': ' : '') . $expense->description,
                'createdBy' => auth()->id() ?: null,
            ]);
            $hareket->delete();
        }
        $this->auditService->logDelete('expense', $expense->id, ['amount' => (float) $expense->amount, 'description' => $expense->description]);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Gider silindi.');
    }
}
