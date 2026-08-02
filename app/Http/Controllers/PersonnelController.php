<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Services\AuditService;
use App\Services\PersonnelAccessService;
use Carbon\Carbon;
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
        $user = $request->user();
        if ($user && ! $user->isAdmin()) {
            $linked = $user->personnel;
            if ($linked) {
                return redirect()->route('personnel.show', $linked);
            }

            abort(403, 'Personel kaydınız bulunamadı.');
        }

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
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $personnel = new Personnel;

        return view('personnel.create', compact('personnel'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

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
        $this->authorizeView($personnel);

        $personnel->load('user');

        $salesQuery = $personnel->sales()->with('customer')->orderByDesc('saleDate')->orderByDesc('createdAt');

        $activeSalesQuery = (clone $salesQuery)->where('isCancelled', false);

        $salesStats = (object) [
            'count' => (clone $salesQuery)->count(),
            'activeCount' => (clone $activeSalesQuery)->count(),
            'total' => (float) (clone $activeSalesQuery)->sum('grandTotal'),
            'totalReceivable' => (float) (clone $activeSalesQuery)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
                ->value('receivable'),
            'monthCount' => (clone $activeSalesQuery)->whereMonth('saleDate', now()->month)->whereYear('saleDate', now()->year)->count(),
        ];

        $sales = $salesQuery->paginate(20)->withQueryString();

        $terminHorizon = Carbon::today()->addDays(7);

        $upcomingDueSales = $personnel->sales()
            ->with('customer')
            ->where('isCancelled', false)
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->orderBy('dueDate')
            ->get();

        $quotes = $personnel->quotes()->with('customer')->orderByDesc('createdAt')->limit(10)->get();

        $viewingOwnProfile = auth()->user()?->personnel?->id === $personnel->id;

        return view('personnel.show', compact('personnel', 'sales', 'quotes', 'salesStats', 'viewingOwnProfile', 'upcomingDueSales'));
    }

    public function edit(Personnel $personnel)
    {
        $this->authorizeManage($personnel);
        $personnel->load('user');

        return view('personnel.edit', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel)
    {
        $this->authorizeManage($personnel);

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
        $this->authorizeManage($personnel);
        $this->removePhotoFile($personnel);
        $personnel->update(['photoUrl' => null]);

        return redirect()->route('personnel.edit', $personnel)->with('success', 'Personel resmi silindi.');
    }

    public function destroy(Personnel $personnel)
    {
        $this->authorizeManage($personnel);
        $this->accessService->disableAccess($personnel);
        $this->removePhotoFile($personnel);
        $this->auditService->logDelete('personnel', $personnel->id, ['name' => $personnel->name]);
        $personnel->delete();

        return redirect()->route('personnel.index')->with('success', 'Personel silindi.');
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

    private function authorizeView(Personnel $personnel): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->personnel?->id === $personnel->id) {
            return;
        }

        abort(403, 'Bu personel kaydını görüntüleme yetkiniz yok.');
    }

    private function authorizeManage(Personnel $personnel): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Personel düzenleme yetkisi yalnızca yöneticidedir.');
        }
    }
}
