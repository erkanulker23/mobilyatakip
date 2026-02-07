<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Kasa;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
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
            $query->where('category', $request->category);
        }
        $total = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();
        return view('expenses.index', compact('expenses', 'total'));
    }

    public function create()
    {
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $categories = Expense::distinct()->whereNotNull('category')->where('category', '!=', '')->pluck('category')->sort()->values();
        return view('expenses.create', compact('kasalar', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'expenseDate' => 'required|date',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'kasaId' => 'nullable|exists:kasa,id',
        ]);
        $validated['createdBy'] = auth()->id();
        Expense::create($validated);
        return redirect()->route('expenses.index')->with('success', 'Gider kaydedildi.');
    }
}
