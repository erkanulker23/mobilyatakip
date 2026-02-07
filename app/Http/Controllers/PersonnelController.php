<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;

class PersonnelController extends Controller
{
    public function index(Request $request)
    {
        $q = Personnel::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $personnel = $q->paginate(20)->withQueryString();
        return view('personnel.index', compact('personnel'));
    }

    public function create()
    {
        return view('personnel.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        Personnel::create($validated);
        return redirect()->route('personnel.index')->with('success', 'Personel kaydedildi.');
    }

    public function show(Personnel $personnel)
    {
        $personnel->load('quotes');
        return view('personnel.show', compact('personnel'));
    }

    public function edit(Personnel $personnel)
    {
        return view('personnel.edit', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        $validated['isActive'] = $request->boolean('isActive');
        $personnel->update($validated);
        return redirect()->route('personnel.index')->with('success', 'Personel güncellendi.');
    }

    public function destroy(Personnel $personnel)
    {
        $personnel->delete();
        return redirect()->route('personnel.index')->with('success', 'Personel silindi.');
    }
}
