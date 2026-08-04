<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogPruner
{
    public const RETENTION_DAYS = 5;

    public const KEEP_PER_USER = 15;

    public static function pruneForUser(?string $userId): int
    {
        if (! $userId) {
            return 0;
        }

        $deleted = AuditLog::query()
            ->where('userId', $userId)
            ->where('createdAt', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();

        $keepIds = AuditLog::query()
            ->where('userId', $userId)
            ->orderByDesc('createdAt')
            ->limit(self::KEEP_PER_USER)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return $deleted;
        }

        $deleted += AuditLog::query()
            ->where('userId', $userId)
            ->whereNotIn('id', $keepIds->all())
            ->delete();

        return $deleted;
    }

    public static function pruneAll(): int
    {
        $deleted = 0;

        AuditLog::query()
            ->whereNotNull('userId')
            ->distinct()
            ->pluck('userId')
            ->each(function (string $userId) use (&$deleted) {
                $deleted += self::pruneForUser($userId);
            });

        $deleted += AuditLog::query()
            ->whereNull('userId')
            ->where('createdAt', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();

        return $deleted;
    }
}
