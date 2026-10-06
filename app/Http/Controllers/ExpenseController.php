<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\PersonnelAdvance;
use App\Models\Sale;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ExpenseController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $with = ['kasa', 'createdByUser'];
        if ($this->expensesHaveSaleLink()) {
            $with[] = 'sale.customer';
        }

        $query = Expense::with($with)->orderBy('expenseDate', 'desc');
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
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%");
                if ($this->expensesHaveSaleLink()) {
                    $q->orWhereHas('sale', function ($sale) use ($s) {
                        $sale->where('saleNumber', 'like', "%{$s}%")
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
                    });
                }
            });
        }
        if ($request->filled('kasaId')) {
            $query->where('kasaId', $request->kasaId);
        }
        if ($request->filled('saleId') && $this->expensesHaveSaleLink()) {
            $query->where('saleId', $request->saleId);
        }
        $total = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();
        $kasalar = Kasa::orderBy('name')->get();

        $advances = collect();
        $advanceTotal = 0.0;
        $categoryFilter = mb_strtolower(trim((string) $request->input('category', '')));
        $showAdvances = $categoryFilter === ''
            || str_contains($categoryFilter, 'avans')
            || str_contains($categoryFilter, 'personel');

        if ($showAdvances && Schema::hasTable('personnel_advances')) {
            $advanceQuery = PersonnelAdvance::with(['personnel', 'kasa'])->orderByDesc('advanceDate')->orderByDesc('createdAt');
            if ($request->filled('from')) {
                $advanceQuery->whereDate('advanceDate', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $advanceQuery->whereDate('advanceDate', '<=', $request->to);
            }
            if ($request->filled('kasaId')) {
                $advanceQuery->where('kasaId', $request->kasaId);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $advanceQuery->where(function ($q) use ($s) {
                    $q->where('note', 'like', "%{$s}%")
                        ->orWhereHas('personnel', fn ($p) => $p->where('name', 'like', "%{$s}%"));
                });
            }
            $advances = $advanceQuery->get();
            $advanceTotal = (float) $advances->sum('amount');
        }

        return view('expenses.index', compact('expenses', 'total', 'kasalar', 'advances', 'advanceTotal'));
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

    /** Sipariş seçimi için son satışlar. */
    private function saleOptions(?string $includeSaleId = null)
    {
        $query = Sale::query()
            ->with('customer')
            ->where('isCancelled', false)
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->limit(250);

        $sales = $query->get();

        if ($includeSaleId && ! $sales->contains(fn (Sale $s) => (string) $s->id === (string) $includeSaleId)) {
            $extra = Sale::with('customer')->find($includeSaleId);
            if ($extra) {
                $sales = $sales->prepend($extra);
            }
        }

        return $sales;
    }

    private function expensesHaveSaleLink(): bool
    {
        return Schema::hasColumn('expenses', 'saleId');
    }

    public function create(Request $request)
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = $this->categoriesForForm();
        $sales = $this->expensesHaveSaleLink()
            ? $this->saleOptions($request->input('saleId'))
            : collect();
        $preselectedSaleId = $request->input('saleId');

        return view('expenses.create', compact('kasalar', 'categories', 'sales', 'preselectedSaleId'));
    }

    public function store(Request $request)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ];
        if ($this->expensesHaveSaleLink()) {
            $rules['saleId'] = 'nullable|exists:sales,id';
        }

        $validated = $request->validate($rules);
        if (! $this->expensesHaveSaleLink()) {
            unset($validated['saleId']);
        } else {
            $validated['saleId'] = $validated['saleId'] ?? null;
        }
        $validated['createdBy'] = auth()->id() ?: null;
        $validated['kdvIncluded'] = true;
        $validated['kdvRate'] = 0;
        $validated['kdvAmount'] = 0;
        $expense = Expense::create($validated);
        $expense->load('sale');
        $this->syncExpenseKasaMovement($expense, $validated);
        $this->auditService->logCreate('expense', $expense->id, [
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'saleId' => $validated['saleId'] ?? null,
        ]);

        return redirect()->route('expenses.show', $expense)->with('success', 'Gider kaydedildi.');
    }

    public function show(Expense $expense)
    {
        $with = ['kasa', 'createdByUser'];
        if ($this->expensesHaveSaleLink()) {
            $with[] = 'sale.customer';
        }
        $expense->load($with);

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = $this->categoriesForForm();
        $sales = $this->expensesHaveSaleLink()
            ? $this->saleOptions($expense->saleId)
            : collect();

        return view('expenses.edit', compact('expense', 'kasalar', 'categories', 'sales'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ];
        if ($this->expensesHaveSaleLink()) {
            $rules['saleId'] = 'nullable|exists:sales,id';
        }

        $validated = $request->validate($rules);
        if (! $this->expensesHaveSaleLink()) {
            unset($validated['saleId']);
        } else {
            $validated['saleId'] = $validated['saleId'] ?? null;
        }
        $validated['kdvIncluded'] = true;
        $validated['kdvRate'] = 0;
        $validated['kdvAmount'] = 0;
        $expense->update($validated);
        $expense->load('sale');

        $this->syncExpenseKasaMovement($expense, $validated);

        $this->auditService->logUpdate('expense', $expense->id, [], [
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'saleId' => $validated['saleId'] ?? null,
        ]);

        return redirect()->route('expenses.show', $expense)->with('success', 'Gider güncellendi.');
    }

    public function destroy(Expense $expense)
    {
        KasaHareket::where('refType', 'expense')->where('refId', $expense->id)->delete();
        $this->auditService->logDelete('expense', $expense->id, ['amount' => (float) $expense->amount, 'description' => $expense->description]);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gider silindi.');
    }

    /** @param  array<string, mixed>  $validated */
    private function syncExpenseKasaMovement(Expense $expense, array $validated): void
    {
        $hareket = KasaHareket::where('refType', 'expense')->where('refId', $expense->id)->first();
        $description = 'Gider - ' . ($validated['category'] ? $validated['category'] . ': ' : '') . ($validated['description'] ?? '');
        $sale = $expense->sale ?? (isset($validated['saleId']) ? Sale::find($validated['saleId']) : null);
        if ($sale) {
            $description .= ' · Sipariş: ' . $sale->saleNumber;
        }

        if (empty($validated['kasaId'])) {
            $hareket?->delete();

            return;
        }

        $payload = [
            'kasaId' => $validated['kasaId'],
            'type' => 'cikis',
            'amount' => -(float) $validated['amount'],
            'movementDate' => $validated['expenseDate'],
            'description' => $description,
            'createdBy' => auth()->id() ?: null,
            'refType' => 'expense',
            'refId' => $expense->id,
        ];

        if ($hareket) {
            $hareket->update($payload);

            return;
        }

        KasaHareket::create($payload);
    }
}
