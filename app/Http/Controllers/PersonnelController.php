<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogPruner;
use App\Models\Personnel;
use App\Models\Sale;
use App\Models\UserTask;
use App\Support\UserTaskCompletion;
use App\Services\AuditService;
use App\Services\PersonnelAccessService;
use App\Support\ActivityMessage;
use App\Support\PersonnelCategory;
use App\Support\PersonnelSalesStats;
use App\Support\SaleDelivery;
use App\Support\WorkshopDashboard;
use App\Support\WorkshopUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PersonnelController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private PersonnelAccessService $accessService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && $user->isWorkshop() && ! $user->isAdmin()) {
            return redirect()->route('workshop.dashboard');
        }

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
        if ($request->filled('category')) {
            $q->where('category', $request->category);
        }
        $personnel = $q->paginate(20)->withQueryString();

        $categoryOptions = PersonnelCategory::options();

        return view('personnel.index', compact('personnel', 'categoryOptions'));
    }

    public function create()
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $personnel = new Personnel;
        $categoryOptions = PersonnelCategory::options();

        return view('personnel.create', compact('personnel', 'categoryOptions'));
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
            'category' => ['nullable', 'string', Rule::in(PersonnelCategory::values())],
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
        if (auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin()) {
            return redirect()->route('workshop.dashboard');
        }

        $this->authorizeView($personnel);

        $personnel->load('user');

        if (WorkshopUser::isWorkshopCategory($personnel->category)) {
            if (auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin()) {
                return redirect()->route('workshop.dashboard');
            }

            return view('personnel.show-workshop', WorkshopDashboard::viewData($personnel));
        }

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

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthStats = $this->personnelPeriodStats($personnel, $thisMonthStart, $thisMonthEnd);
        $lastMonthStats = $this->personnelPeriodStats($personnel, $lastMonthStart, $lastMonthEnd);

        $monthlyPerformance = [
            'thisMonth' => [
                'label' => $thisMonthStart->locale('tr')->isoFormat('MMMM YYYY'),
                ...$thisMonthStats,
            ],
            'lastMonth' => [
                'label' => $lastMonthStart->locale('tr')->isoFormat('MMMM YYYY'),
                ...$lastMonthStats,
            ],
            'countChange' => $this->periodChangePercent($thisMonthStats['count'], $lastMonthStats['count']),
            'totalChange' => $this->periodChangePercent($thisMonthStats['total'], $lastMonthStats['total']),
            'collectedChange' => $this->periodChangePercent($thisMonthStats['collected'], $lastMonthStats['collected']),
        ];

        $sales = $salesQuery->paginate(20)->withQueryString();

        $terminHorizon = Carbon::today()->addDays(7);

        $upcomingDueSales = $personnel->sales()
            ->with('customer')
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->orderBy('dueDate')
            ->get();

        $quotes = $personnel->quotes()->with('customer')->orderByDesc('createdAt')->limit(10)->get();

        $personnelTasks = collect();
        $taskCompleterFallback = [];
        if ($personnel->hasSystemAccess()) {
            $personnelTasks = UserTask::query()
                ->with('completedByUser:id,name')
                ->where(function ($q) use ($personnel) {
                    $q->where('personnelId', $personnel->id);
                    if ($personnel->userId) {
                        $q->orWhere('userId', $personnel->userId);
                    }
                })
                ->orderBy('isCompleted')
                ->orderByRaw('dueDate IS NULL, dueDate ASC')
                ->orderBy('sortOrder')
                ->orderByDesc('createdAt')
                ->get();
            $taskCompleterFallback = UserTaskCompletion::completerNameMap($personnelTasks);
        }

        $viewingOwnProfile = auth()->user()?->personnel?->id === $personnel->id;

        return view('personnel.show', compact('personnel', 'sales', 'quotes', 'salesStats', 'viewingOwnProfile', 'upcomingDueSales', 'monthlyPerformance', 'personnelTasks', 'taskCompleterFallback'));
    }

    public function activities(Request $request, Personnel $personnel)
    {
        $this->authorizeView($personnel);

        $personnel->load('user');
        $viewingOwnProfile = auth()->user()?->personnel?->id === $personnel->id;

        $logs = collect();
        $logRetentionDays = AuditLogPruner::RETENTION_DAYS;
        $logKeepLimit = AuditLogPruner::KEEP_PER_USER;

        if ($personnel->userId) {
            AuditLogPruner::pruneForUser($personnel->userId);

            $query = AuditLog::query()
                ->where('userId', $personnel->userId)
                ->orderByDesc('createdAt');

            if ($request->filled('entity')) {
                $query->where('entity', $request->entity);
            }
            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($w) use ($s) {
                    $w->where('entity', 'like', "%{$s}%")
                        ->orWhere('action', 'like', "%{$s}%")
                        ->orWhere('entityId', 'like', "%{$s}%")
                        ->orWhere('oldValue', 'like', "%{$s}%")
                        ->orWhere('newValue', 'like', "%{$s}%");
                });
            }

            $logs = $query->limit($logKeepLimit)->get();
        }

        $entityOptions = ActivityMessage::filterEntityOptions();
        $actionOptions = ActivityMessage::filterActionOptions();

        return view('personnel.activities', compact(
            'personnel',
            'logs',
            'viewingOwnProfile',
            'entityOptions',
            'actionOptions',
            'logRetentionDays',
            'logKeepLimit'
        ));
    }

    public function edit(Personnel $personnel)
    {
        $this->authorizeManage($personnel);
        $personnel->load('user');
        $categoryOptions = PersonnelCategory::options();

        return view('personnel.edit', compact('personnel', 'categoryOptions'));
    }

    public function update(Request $request, Personnel $personnel)
    {
        $this->authorizeManage($personnel);

        $canAccess = $request->boolean('canAccessSystem');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:100',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'category' => ['nullable', 'string', Rule::in(PersonnelCategory::values())],
            'title' => 'nullable|string|max:100',
            'isActive' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ];

        if ($canAccess && $this->accessService->canManageAccess($request->user())) {
            $rules['email'] = 'required|email|max:100';
        }

        if ($this->accessService->canManageAccess($request->user())) {
            $rules = array_merge($rules, $this->accessService->validationRules($personnel, $canAccess));
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'email.required' => 'Sistem erişimi için e-posta adresi zorunludur.',
        ]);

        unset($validated['photo'], $validated['canAccessSystem'], $validated['systemRole'], $validated['password'], $validated['password_confirmation']);

        $validated['isActive'] = $request->boolean('isActive', true);

        if ($request->hasFile('photo')) {
            $this->removePhotoFile($personnel);
            $validated['photoUrl'] = '/storage/'.$request->file('photo')->store('personnel', 'public');
        }

        $oldData = ['name' => $personnel->name];

        DB::transaction(function () use ($request, $personnel, $validated, $canAccess) {
            $personnel->update($validated);

            if ($this->accessService->canManageAccess($request->user())) {
                $this->accessService->sync($personnel->fresh(), [
                    'canAccessSystem' => $canAccess,
                    'systemRole' => $request->input('systemRole', 'staff'),
                    'password' => $request->input('password'),
                ]);
            } else {
                $this->accessService->syncActiveState($personnel->fresh());
            }
        });

        $this->auditService->logUpdate('personnel', $personnel->id, $oldData, ['name' => $personnel->fresh()->name]);

        return redirect()->route('personnel.show', $personnel)->with('success', 'Personel güncellendi.');
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

    private function personnelPeriodStats(Personnel $personnel, Carbon $start, Carbon $end): array
    {
        $base = $personnel->sales()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$start->toDateString(), $end->toDateString()]);

        return [
            'count' => (int) (clone $base)->count(),
            'total' => (float) (clone $base)->sum('grandTotal'),
            'collected' => PersonnelSalesStats::collectedInPeriod($personnel, $start, $end),
        ];
    }

    private function periodChangePercent(float|int $current, float|int $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous <= 0.005) {
            return $current > 0.005 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
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
