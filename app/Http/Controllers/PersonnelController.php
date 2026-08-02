<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Services\AuditService;
use App\Services\PersonnelAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonnelController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private PersonnelAccessService $accessService,
    ) {}

    public function index(Request $request)
    {
        $q = Personnel::query()->with('user')->orderBy('name');
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
        $personnel = new Personnel;

        return view('personnel.create', compact('personnel'));
    }

    public function store(Request $request)
    {
        $canAccess = $request->boolean('canAccessSystem');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ];

        if ($this->accessService->canManageAccess($request->user())) {
            $rules = array_merge($rules, $this->accessService->validationRules(new Personnel, $canAccess));
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);

        unset($validated['photo'], $validated['canAccessSystem'], $validated['systemRole'], $validated['password'], $validated['password_confirmation']);

        if ($request->hasFile('photo')) {
            $validated['photoUrl'] = '/storage/'.$request->file('photo')->store('personnel', 'public');
        }

        $person = Personnel::create($validated);

        if ($this->accessService->canManageAccess($request->user())) {
            $this->accessService->sync($person, [
                'canAccessSystem' => $canAccess,
                'systemRole' => $request->input('systemRole', 'staff'),
                'password' => $request->input('password'),
            ]);
        }

        $this->auditService->logCreate('personnel', $person->id, ['name' => $person->name]);

        return redirect()->route('personnel.index')->with('success', 'Personel kaydedildi.');
    }

    public function show(Personnel $personnel)
    {
        $personnel->load(['quotes', 'user']);

        return view('personnel.show', compact('personnel'));
    }

    public function edit(Personnel $personnel)
    {
        $personnel->load('user');

        return view('personnel.edit', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel)
    {
        $canAccess = $request->boolean('canAccessSystem');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ];

        if ($this->accessService->canManageAccess($request->user())) {
            $rules = array_merge($rules, $this->accessService->validationRules($personnel, $canAccess));
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);

        unset($validated['photo'], $validated['canAccessSystem'], $validated['systemRole'], $validated['password'], $validated['password_confirmation']);

        $validated['isActive'] = $request->boolean('isActive');

        if ($request->hasFile('photo')) {
            $this->removePhotoFile($personnel);
            $validated['photoUrl'] = '/storage/'.$request->file('photo')->store('personnel', 'public');
        }

        $oldData = ['name' => $personnel->name];
        $personnel->update($validated);

        if ($this->accessService->canManageAccess($request->user())) {
            $this->accessService->sync($personnel, [
                'canAccessSystem' => $canAccess,
                'systemRole' => $request->input('systemRole', 'staff'),
                'password' => $request->input('password'),
            ]);
        } else {
            $this->accessService->syncActiveState($personnel->fresh());
        }

        $this->auditService->logUpdate('personnel', $personnel->id, $oldData, ['name' => $personnel->name]);

        return redirect()->route('personnel.index')->with('success', 'Personel güncellendi.');
    }

    public function deletePhoto(Personnel $personnel)
    {
        $this->removePhotoFile($personnel);
        $personnel->update(['photoUrl' => null]);

        return redirect()->route('personnel.edit', $personnel)->with('success', 'Personel resmi silindi.');
    }

    private function removePhotoFile(Personnel $personnel): void
    {
        if (! $personnel->photoUrl) {
            return;
        }
        $path = str_replace('/storage/', '', parse_url($personnel->photoUrl, PHP_URL_PATH));
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function destroy(Personnel $personnel)
    {
        $this->accessService->disableAccess($personnel);
        $this->removePhotoFile($personnel);
        $this->auditService->logDelete('personnel', $personnel->id, ['name' => $personnel->name]);
        $personnel->delete();

        return redirect()->route('personnel.index')->with('success', 'Personel silindi.');
    }
}
