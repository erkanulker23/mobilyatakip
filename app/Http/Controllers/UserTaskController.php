<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserTask;
use App\Services\AuditService;
use App\Support\UserTaskColor;
use App\Support\UserTaskCompletion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTaskController extends Controller
{
    public const ASSIGN_ALL_PERSONNEL = 'all';

    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $query = UserTask::query()->with([
            'user:id,name,role',
            'personnel:id,name,title,userId',
            'completedByUser:id,name',
        ]);

        $this->applyTaskVisibilityScope($query, $user, $request);

        if ($user->isAdmin() || $user->canManageTeamTasks()) {
            if ($request->filled('personnelId')) {
                $query->where('personnelId', $request->personnelId);
            } elseif ($request->filled('userId')) {
                $query->where('userId', $request->userId);
            }
        }

        $query->where(function ($w) use ($monthStart, $monthEnd) {
            $w->whereBetween('dueDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orWhere('isCompleted', false)
                ->orWhereBetween('completedAt', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()]);
        });

        $tasks = $query
            ->orderBy('isCompleted')
            ->orderBy('dueDate')
            ->orderBy('sortOrder')
            ->orderByDesc('createdAt')
            ->get();

        $completerFallback = UserTaskCompletion::completerNameMap($tasks);

        return response()->json([
            'tasks' => $tasks->map(fn (UserTask $task) => $this->taskPayload($task, $completerFallback)),
            'month' => $monthStart->format('Y-m'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'dueDate' => 'nullable|date',
            'color' => 'nullable|string|in:' . implode(',', UserTaskColor::keys()),
            'personnelId' => 'nullable|string',
            'userId' => 'nullable|string',
        ]);

        $ownerId = (string) $request->user()->id;
        $personnelId = null;

        if (
            $request->user()->canManageTeamTasks()
            && ($validated['personnelId'] ?? '') === self::ASSIGN_ALL_PERSONNEL
        ) {
            return $this->storeForAllPersonnel($request, $validated);
        }

        if ($request->user()->canManageTeamTasks() && ! empty($validated['personnelId'])) {
            [$personnelId, $ownerId] = $this->resolvePersonnelAssignment(
                $request,
                (string) $validated['personnelId']
            );
        } elseif ($request->user()->canManageTeamTasks() && ! empty($validated['userId'])) {
            $ownerId = (string) $validated['userId'];
            if (! User::where('id', $ownerId)->where('isActive', true)->exists()) {
                return response()->json(['message' => 'Geçersiz kullanıcı.'], 422);
            }
        } elseif ($request->user()->personnel?->id) {
            $personnelId = $request->user()->personnel->id;
            $ownerId = (string) $request->user()->id;
        }

        $task = UserTask::create([
            'userId' => $ownerId,
            'personnelId' => $personnelId,
            'title' => trim($validated['title']),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            'dueDate' => $validated['dueDate'] ?? null,
            'color' => UserTaskColor::normalize($validated['color'] ?? null),
        ]);

        $this->auditService->logCreate('user_task', $task->id, ['title' => $task->title]);

        $task->load([
            'user:id,name,role',
            'personnel:id,name,title,userId',
            'completedByUser:id,name',
        ]);

        return response()->json([
            'task' => $this->taskPayload($task),
            'message' => 'Görev eklendi.',
        ], 201);
    }

    public function update(Request $request, UserTask $userTask)
    {
        $this->authorizeTask($userTask);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'dueDate' => 'nullable|date',
            'color' => 'nullable|string|in:' . implode(',', UserTaskColor::allowedKeys()),
            'isCompleted' => 'sometimes|boolean',
            'personnelId' => 'nullable|string',
        ]);

        $updates = [];
        $oldAudit = [
            'title' => $userTask->title,
            'notes' => $userTask->notes,
            'dueDate' => $userTask->dueDate?->format('Y-m-d'),
            'color' => $userTask->color,
        ];
        if (array_key_exists('title', $validated)) {
            $updates['title'] = trim($validated['title']);
        }
        if (array_key_exists('notes', $validated)) {
            $updates['notes'] = $validated['notes'] !== null ? trim($validated['notes']) : null;
        }
        if (array_key_exists('dueDate', $validated)) {
            $updates['dueDate'] = $validated['dueDate'];
        }
        if (array_key_exists('color', $validated)) {
            $updates['color'] = UserTaskColor::normalize($validated['color']);
        }
        if (array_key_exists('isCompleted', $validated)) {
            $updates['isCompleted'] = (bool) $validated['isCompleted'];
            if ($validated['isCompleted']) {
                $updates['completedAt'] = now();
                $updates['completedByUserId'] = (string) $request->user()->id;
            } else {
                $updates['completedAt'] = null;
                $updates['completedByUserId'] = null;
            }
        }

        if (array_key_exists('personnelId', $validated)) {
            if ($request->user()->canManageTeamTasks()) {
                if (($validated['personnelId'] ?? '') === self::ASSIGN_ALL_PERSONNEL) {
                    return $this->reassignToAllPersonnel($request, $userTask, $validated);
                }

                if ($validated['personnelId'] === null || $validated['personnelId'] === '') {
                    $updates['personnelId'] = null;
                } else {
                    [$assignedPersonnelId, $ownerId] = $this->resolvePersonnelAssignment(
                        $request,
                        (string) $validated['personnelId']
                    );
                    $updates['personnelId'] = $assignedPersonnelId;
                    $updates['userId'] = $ownerId;
                }
            }
        }

        $userTask->update($updates);
        $userTask->load([
            'user:id,name,role',
            'personnel:id,name,title,userId',
            'completedByUser:id,name',
        ]);

        if (array_key_exists('isCompleted', $updates) && $updates['isCompleted']) {
            $this->auditService->logAction('user_task', $userTask->id, 'complete', ['title' => $userTask->title]);
        } elseif ($updates !== []) {
            $this->auditService->logUpdate('user_task', $userTask->id, $oldAudit, [
                'title' => $userTask->title,
                'notes' => $userTask->notes,
                'dueDate' => $userTask->dueDate?->format('Y-m-d'),
                'color' => $userTask->color,
            ]);
        }

        return response()->json([
            'task' => $this->taskPayload($userTask),
            'message' => 'Görev güncellendi.',
        ]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'taskIds' => 'required|array|min:1',
            'taskIds.*' => 'required|string',
        ]);

        $taskIds = array_values(array_unique($validated['taskIds']));
        $tasks = UserTask::query()->whereIn('id', $taskIds)->get()->keyBy('id');

        if ($tasks->count() !== count($taskIds)) {
            return response()->json(['message' => 'Geçersiz görev seçimi.'], 422);
        }

        foreach ($taskIds as $taskId) {
            $this->authorizeTask($tasks->get($taskId));
        }

        DB::transaction(function () use ($taskIds, $tasks) {
            foreach ($taskIds as $index => $taskId) {
                $tasks->get($taskId)?->update(['sortOrder' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Görev sırası güncellendi.']);
    }

    public function destroy(UserTask $userTask)
    {
        $this->authorizeTask($userTask);
        $title = $userTask->title;
        $taskId = $userTask->id;
        $userTask->delete();

        $this->auditService->logDelete('user_task', $taskId, ['title' => $title]);

        return response()->json(['message' => 'Görev silindi.']);
    }

    private function storeForAllPersonnel(Request $request, array $validated): \Illuminate\Http\JsonResponse
    {
        $personnelList = Personnel::query()
            ->where('isActive', true)
            ->orderBy('name')
            ->get();

        if ($personnelList->isEmpty()) {
            return response()->json(['message' => 'Atanacak aktif personel bulunamadı.'], 422);
        }

        $tasks = $this->createTasksForPersonnel(
            $request,
            $this->taskDataFromValidated($validated),
            $personnelList
        );

        return $this->tasksCreatedResponse($tasks, 201);
    }

    private function reassignToAllPersonnel(Request $request, UserTask $userTask, array $validated): \Illuminate\Http\JsonResponse
    {
        $personnelList = Personnel::query()
            ->where('isActive', true)
            ->orderBy('name')
            ->get();

        if ($personnelList->isEmpty()) {
            return response()->json(['message' => 'Atanacak aktif personel bulunamadı.'], 422);
        }

        $taskData = $this->taskDataFromUpdate($userTask, $validated);
        $taskId = $userTask->id;
        $taskTitle = $userTask->title;

        $userTask->delete();
        $this->auditService->logDelete('user_task', $taskId, ['title' => $taskTitle]);

        $tasks = $this->createTasksForPersonnel($request, $taskData, $personnelList);

        return $this->tasksCreatedResponse($tasks, 200, 'Görev tüm personele atandı.');
    }

    /** @param  \Illuminate\Support\Collection<int, Personnel>  $personnelList
     * @return \Illuminate\Database\Eloquent\Collection<int, UserTask> */
    private function createTasksForPersonnel(Request $request, array $taskData, $personnelList)
    {
        $creatorId = (string) $request->user()->id;
        $tasks = new \Illuminate\Database\Eloquent\Collection();

        foreach ($personnelList as $personnel) {
            $ownerId = $personnel->userId
                ? (string) $personnel->userId
                : $creatorId;

            $task = UserTask::create([
                'userId' => $ownerId,
                'personnelId' => (string) $personnel->id,
                ...$taskData,
            ]);

            $this->auditService->logCreate('user_task', $task->id, ['title' => $task->title]);
            $tasks->push($task);
        }

        $tasks->load([
            'user:id,name,role',
            'personnel:id,name,title,userId',
            'completedByUser:id,name',
        ]);

        return $tasks;
    }

    /** @param  \Illuminate\Support\Collection<int, UserTask>  $tasks */
    private function tasksCreatedResponse($tasks, int $status = 201, ?string $message = null): \Illuminate\Http\JsonResponse
    {
        $count = $tasks->count();
        $firstTask = $tasks->first();

        return response()->json([
            'task' => $firstTask ? $this->taskPayload($firstTask) : null,
            'tasks' => $tasks->map(fn (UserTask $task) => $this->taskPayload($task))->values(),
            'reloaded' => true,
            'message' => $message ?? ($count === 1
                ? '1 personele görev eklendi.'
                : $count . ' personele görev eklendi.'),
        ], $status);
    }

    /** @return array{title: string, notes: ?string, dueDate: ?string, color: string, isCompleted: bool, completedAt: ?\Illuminate\Support\Carbon, completedByUserId: ?string} */
    private function taskDataFromValidated(array $validated): array
    {
        return [
            'title' => trim($validated['title']),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            'dueDate' => $validated['dueDate'] ?? null,
            'color' => UserTaskColor::normalize($validated['color'] ?? null),
            'isCompleted' => false,
            'completedAt' => null,
            'completedByUserId' => null,
        ];
    }

    /** @return array{title: string, notes: ?string, dueDate: ?string, color: string, isCompleted: bool, completedAt: ?\Illuminate\Support\Carbon, completedByUserId: ?string} */
    private function taskDataFromUpdate(UserTask $userTask, array $validated): array
    {
        $isCompleted = array_key_exists('isCompleted', $validated)
            ? (bool) $validated['isCompleted']
            : (bool) $userTask->isCompleted;

        $completedAt = $userTask->completedAt;
        $completedByUserId = $userTask->completedByUserId ? (string) $userTask->completedByUserId : null;

        if (array_key_exists('isCompleted', $validated)) {
            if ($validated['isCompleted']) {
                $completedAt = now();
                $completedByUserId = (string) request()->user()->id;
            } else {
                $completedAt = null;
                $completedByUserId = null;
            }
        }

        return [
            'title' => array_key_exists('title', $validated) ? trim($validated['title']) : $userTask->title,
            'notes' => array_key_exists('notes', $validated)
                ? ($validated['notes'] !== null ? trim($validated['notes']) : null)
                : $userTask->notes,
            'dueDate' => array_key_exists('dueDate', $validated)
                ? $validated['dueDate']
                : $userTask->dueDate?->format('Y-m-d'),
            'color' => array_key_exists('color', $validated)
                ? UserTaskColor::normalize($validated['color'])
                : UserTaskColor::normalize($userTask->color),
            'isCompleted' => $isCompleted,
            'completedAt' => $completedAt,
            'completedByUserId' => $completedByUserId,
        ];
    }

    private function authorizeTask(UserTask $task): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Bu görevi yönetme yetkiniz yok.');
        }

        if ($user->isAdmin() || $user->canManageTeamTasks()) {
            return;
        }

        $personnelId = $user->personnel?->id;
        $ownsTask = (string) $task->userId === (string) $user->id
            || ($personnelId && (string) $task->personnelId === (string) $personnelId);

        if (! $ownsTask) {
            abort(403, 'Bu görevi yönetme yetkiniz yok.');
        }
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<UserTask>  $query */
    private function applyTaskVisibilityScope($query, User $user, Request $request): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->canManageTeamTasks() && $request->input('scope') !== 'personal') {
            return;
        }

        $personnelId = $user->personnel?->id;

        $query->where(function ($w) use ($user, $personnelId) {
            if ($personnelId) {
                $w->where('personnelId', $personnelId)
                    ->orWhere(function ($inner) use ($user) {
                        $inner->where('userId', $user->id)->whereNull('personnelId');
                    });
            } else {
                $w->where('userId', $user->id);
            }
        });
    }

    /** @return array{0: string, 1: string} personnelId, owner userId */
    private function resolvePersonnelAssignment(Request $request, string $personnelId): array
    {
        $personnel = Personnel::query()
            ->where('id', $personnelId)
            ->where('isActive', true)
            ->first();

        if (! $personnel) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'personnelId' => 'Geçersiz personel seçimi.',
            ]);
        }

        $ownerId = $personnel->userId
            ? (string) $personnel->userId
            : (string) $request->user()->id;

        return [(string) $personnel->id, $ownerId];
    }

    private function taskPayload(UserTask $task, array $completerFallback = []): array
    {
        $color = UserTaskColor::normalize($task->color);
        $classes = UserTaskColor::classes($color);
        $assigneeName = $task->personnel?->name ?? $task->user?->name;
        $completedByName = UserTaskCompletion::completerName($task, $completerFallback);

        return [
            'id' => $task->id,
            'userId' => (string) $task->userId,
            'userName' => $task->user?->name,
            'userRole' => $task->user?->role,
            'personnelId' => $task->personnelId ? (string) $task->personnelId : null,
            'personnelName' => $task->personnel?->name,
            'personnelTitle' => $task->personnel?->title,
            'assigneeName' => $assigneeName,
            'title' => $task->title,
            'notes' => $task->notes,
            'dueDate' => $task->dueDate?->format('Y-m-d'),
            'color' => $color,
            'colorLabel' => $classes['label'] ?? '',
            'isCompleted' => (bool) $task->isCompleted,
            'completedAt' => $task->completedAt?->toIso8601String(),
            'completedByUserId' => $task->completedByUserId ? (string) $task->completedByUserId : null,
            'completedByName' => $completedByName,
            'sortOrder' => (int) $task->sortOrder,
        ];
    }
}
