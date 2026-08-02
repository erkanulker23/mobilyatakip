<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTask;
use App\Support\UserTaskColor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserTaskController extends Controller
{
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

        $query = UserTask::query()->with('user:id,name,role');

        if ($user->isAdmin()) {
            if ($request->filled('userId')) {
                $query->where('userId', $request->userId);
            }
        } else {
            $query->where('userId', $user->id);
        }

        $query->where(function ($w) use ($monthStart, $monthEnd) {
            $w->whereBetween('dueDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orWhere(function ($inner) {
                    $inner->whereNull('dueDate')->where('isCompleted', false);
                });
        });

        $tasks = $query
            ->orderBy('isCompleted')
            ->orderBy('dueDate')
            ->orderBy('sortOrder')
            ->orderByDesc('createdAt')
            ->get()
            ->map(fn (UserTask $task) => $this->taskPayload($task));

        return response()->json([
            'tasks' => $tasks,
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
            'userId' => 'nullable|string',
        ]);

        $ownerId = (string) $request->user()->id;
        if ($request->user()->isAdmin() && ! empty($validated['userId'])) {
            $ownerId = (string) $validated['userId'];
            if (! User::where('id', $ownerId)->where('isActive', true)->exists()) {
                return response()->json(['message' => 'Geçersiz kullanıcı.'], 422);
            }
        }

        $task = UserTask::create([
            'userId' => $ownerId,
            'title' => trim($validated['title']),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            'dueDate' => $validated['dueDate'] ?? null,
            'color' => UserTaskColor::normalize($validated['color'] ?? null),
        ]);

        $task->load('user:id,name,role');

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
            'color' => 'nullable|string|in:' . implode(',', UserTaskColor::keys()),
            'isCompleted' => 'sometimes|boolean',
        ]);

        $updates = [];
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
            $updates['completedAt'] = $validated['isCompleted'] ? ($userTask->completedAt ?? now()) : null;
        }

        $userTask->update($updates);
        $userTask->load('user:id,name,role');

        return response()->json([
            'task' => $this->taskPayload($userTask),
            'message' => 'Görev güncellendi.',
        ]);
    }

    public function destroy(UserTask $userTask)
    {
        $this->authorizeTask($userTask);
        $userTask->delete();

        return response()->json(['message' => 'Görev silindi.']);
    }

    private function authorizeTask(UserTask $task): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }
        if ((string) $task->userId !== (string) $user->id) {
            abort(403, 'Bu görevi yönetme yetkiniz yok.');
        }
    }

    private function taskPayload(UserTask $task): array
    {
        $color = UserTaskColor::normalize($task->color);
        $classes = UserTaskColor::classes($color);

        return [
            'id' => $task->id,
            'userId' => (string) $task->userId,
            'userName' => $task->user?->name,
            'userRole' => $task->user?->role,
            'title' => $task->title,
            'notes' => $task->notes,
            'dueDate' => $task->dueDate?->format('Y-m-d'),
            'color' => $color,
            'colorLabel' => $classes['label'] ?? '',
            'isCompleted' => (bool) $task->isCompleted,
            'completedAt' => $task->completedAt?->toIso8601String(),
            'sortOrder' => (int) $task->sortOrder,
        ];
    }
}
