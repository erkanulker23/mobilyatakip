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

    public function create()
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = array_unique(array_merge(
            ['Kira', 'Elektrik', 'Su', 'Doğalgaz', 'Personel', 'Kırtasiye', 'Vergi', 'Sigorta', 'Bakım', 'Ulaşım', 'Diğer'],
            Expense::distinct()->whereNotNull('category')->where('category', '!=', '')->pluck('category')->toArray()
        ));
        sort($categories);
        return view('expenses.create', compact('kasalar', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'kdvIncluded' => 'nullable|boolean',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ]);
        $validated['createdBy'] = auth()->id();
        $validated['kdvIncluded'] = $request->boolean('kdvIncluded', true);
        $validated['kdvRate'] = isset($validated['kdvRate']) ? (float) $validated['kdvRate'] : 18;
        $amount = (float) $validated['amount'];
        if ($validated['kdvIncluded']) {
            $validated['kdvAmount'] = round($amount - $amount / (1 + $validated['kdvRate'] / 100), 2);
        } else {
            $validated['kdvAmount'] = round($amount * ($validated['kdvRate'] / 100), 2);
        }
        $expense = Expense::create($validated);
        if (!empty($validated['kasaId'])) {
            KasaHareket::create([
                'kasaId' => $validated['kasaId'],
                'type' => 'cikis',
                'amount' => -(float) $validated['amount'],
                'movementDate' => $validated['expenseDate'],
                'description' => 'Gider - ' . ($validated['category'] ? $validated['category'] . ': ' : '') . ($validated['description'] ?? ''),
                'createdBy' => auth()->id(),
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
        $categories = array_unique(array_merge(
            ['Kira', 'Elektrik', 'Su', 'Doğalgaz', 'Personel', 'Kırtasiye', 'Vergi', 'Sigorta', 'Bakım', 'Ulaşım', 'Diğer'],
            Expense::distinct()->whereNotNull('category')->where('category', '!=', '')->pluck('category')->toArray()
        ));
        sort($categories);
        return view('expenses.edit', compact('expense', 'kasalar', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'kdvIncluded' => 'nullable|boolean',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ]);
        $validated['kdvIncluded'] = $request->boolean('kdvIncluded', true);
        $validated['kdvRate'] = isset($validated['kdvRate']) ? (float) $validated['kdvRate'] : 18;
        $amount = (float) $validated['amount'];
        if ($validated['kdvIncluded']) {
            $validated['kdvAmount'] = round($amount - $amount / (1 + $validated['kdvRate'] / 100), 2);
        } else {
            $validated['kdvAmount'] = round($amount * ($validated['kdvRate'] / 100), 2);
        }

        $expense->update($validated);

        $hareket = KasaHareket::where('refType', 'expense')->where('refId', $expense->id)->first();
        if ($hareket) {
            KasaHareket::create([
                'kasaId' => $hareket->kasaId,
                'type' => 'giris',
                'amount' => abs((float) $hareket->amount),
                'movementDate' => $validated['expenseDate'],
                'description' => 'Gider iptal - ' . ($expense->category ? $expense->category . ': ' : '') . $expense->description,
                'createdBy' => auth()->id(),
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
                'createdBy' => auth()->id(),
                'refType' => 'expense',
                'refId' => $expense->id,
            ]);
        }

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
                'createdBy' => auth()->id(),
            ]);
            $hareket->delete();
        }
        $this->auditService->logDelete('expense', $expense->id, ['amount' => (float) $expense->amount, 'description' => $expense->description]);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Gider silindi.');
    }
}
