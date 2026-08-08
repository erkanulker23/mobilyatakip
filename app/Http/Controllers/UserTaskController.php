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

class UserTaskController extends Controller
{
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

        $this->applyTaskVisibilityScope($query, $user);

        if ($user->isAdmin()) {
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

        if ($request->user()->isAdmin()) {
            if (! empty($validated['personnelId'])) {
                $personnel = Personnel::query()
                    ->where('id', $validated['personnelId'])
                    ->where('isActive', true)
                    ->first();

                if (! $personnel) {
                    return response()->json(['message' => 'Geçersiz personel seçimi.'], 422);
                }

                $personnelId = $personnel->id;
                $ownerId = $personnel->userId
                    ? (string) $personnel->userId
                    : (string) $request->user()->id;
            } elseif (! empty($validated['userId'])) {
                $ownerId = (string) $validated['userId'];
                if (! User::where('id', $ownerId)->where('isActive', true)->exists()) {
                    return response()->json(['message' => 'Geçersiz kullanıcı.'], 422);
                }
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
            if ($request->user()->isAdmin()) {
                if ($validated['personnelId'] === null || $validated['personnelId'] === '') {
                    $updates['personnelId'] = null;
                } else {
                    $personnel = Personnel::query()
                        ->where('id', $validated['personnelId'])
                        ->where('isActive', true)
                        ->first();

                    if (! $personnel) {
                        return response()->json(['message' => 'Geçersiz personel seçimi.'], 422);
                    }

                    $updates['personnelId'] = $personnel->id;
                    $updates['userId'] = $personnel->userId
                        ? (string) $personnel->userId
                        : (string) $request->user()->id;
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

    public function destroy(UserTask $userTask)
    {
        $this->authorizeTask($userTask);
        $title = $userTask->title;
        $taskId = $userTask->id;
        $userTask->delete();

        $this->auditService->logDelete('user_task', $taskId, ['title' => $title]);

        return response()->json(['message' => 'Görev silindi.']);
    }

    private function authorizeTask(UserTask $task): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Bu görevi yönetme yetkiniz yok.');
        }

        if ($user->isAdmin()) {
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
    private function applyTaskVisibilityScope($query, User $user): void
    {
        if ($user->isAdmin()) {
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
