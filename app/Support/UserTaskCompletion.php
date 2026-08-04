<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\UserTask;
use Illuminate\Support\Collection;

class UserTaskCompletion
{
    /** @param  iterable<UserTask>  $tasks
     * @return array<string, string>
     */
    public static function completerNameMap(iterable $tasks): array
    {
        $taskIds = collect($tasks)
            ->filter(fn (UserTask $task) => $task->isCompleted && ! $task->completedByUserId)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($taskIds->isEmpty()) {
            return [];
        }

        return AuditLog::query()
            ->where('entity', 'user_task')
            ->where('action', 'complete')
            ->whereIn('entityId', $taskIds)
            ->with('user:id,name')
            ->orderByDesc('createdAt')
            ->get()
            ->unique('entityId')
            ->mapWithKeys(fn (AuditLog $log) => [
                (string) $log->entityId => (string) ($log->user?->name ?? ''),
            ])
            ->filter()
            ->all();
    }

    public static function completerName(UserTask $task, array $fallbackMap = []): ?string
    {
        $name = $task->completedByUser?->name;
        if ($name) {
            return $name;
        }

        $fallback = $fallbackMap[(string) $task->id] ?? null;

        return $fallback !== '' ? $fallback : null;
    }

    public static function backfillFromAuditLogs(): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('user_tasks', 'completedByUserId')) {
            return 0;
        }

        $updated = 0;

        UserTask::query()
            ->where('isCompleted', true)
            ->whereNull('completedByUserId')
            ->orderBy('id')
            ->chunkById(100, function (Collection $tasks) use (&$updated) {
                $map = self::auditCompleterUserIds($tasks->pluck('id')->all());

                foreach ($tasks as $task) {
                    $userId = $map[(string) $task->id] ?? null;
                    if (! $userId) {
                        continue;
                    }

                    $task->update(['completedByUserId' => $userId]);
                    $updated++;
                }
            }, 'id');

        return $updated;
    }

    /** @param  list<string>|Collection<int, string>  $taskIds
     * @return array<string, string> taskId => userId
     */
    private static function auditCompleterUserIds(array|Collection $taskIds): array
    {
        $taskIds = collect($taskIds)->filter()->unique()->values();
        if ($taskIds->isEmpty()) {
            return [];
        }

        return AuditLog::query()
            ->where('entity', 'user_task')
            ->where('action', 'complete')
            ->whereIn('entityId', $taskIds)
            ->whereNotNull('userId')
            ->orderByDesc('createdAt')
            ->get()
            ->unique('entityId')
            ->mapWithKeys(fn (AuditLog $log) => [(string) $log->entityId => (string) $log->userId])
            ->all();
    }
}
