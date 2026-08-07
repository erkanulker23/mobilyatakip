<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Collection;

class QuoteCreator
{
    /** @param  iterable<Quote>  $quotes
     * @return array<string, string>
     */
    public static function creatorNameMapFromAudit(iterable $quotes): array
    {
        $quoteIds = collect($quotes)
            ->filter(fn (Quote $quote) => ! $quote->createdBy)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($quoteIds->isEmpty()) {
            return [];
        }

        return AuditLog::query()
            ->where('entity', 'quote')
            ->where('action', 'create')
            ->whereIn('entityId', $quoteIds)
            ->with('user.personnel:id,userId,name')
            ->orderBy('createdAt')
            ->get()
            ->unique('entityId')
            ->mapWithKeys(function (AuditLog $log) {
                $name = self::displayNameForUser($log->user);

                return $name ? [(string) $log->entityId => $name] : [];
            })
            ->all();
    }

    public static function backfillFromAuditLogs(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('quotes', 'createdBy')) {
            return;
        }

        Quote::query()
            ->whereNull('createdBy')
            ->orderBy('createdAt')
            ->chunkById(100, function (Collection $quotes) {
                $logs = AuditLog::query()
                    ->where('entity', 'quote')
                    ->where('action', 'create')
                    ->whereIn('entityId', $quotes->pluck('id'))
                    ->orderBy('createdAt')
                    ->get()
                    ->unique('entityId')
                    ->keyBy('entityId');

                foreach ($quotes as $quote) {
                    $log = $logs->get($quote->id);
                    if (! $log?->userId) {
                        continue;
                    }

                    $quote->update(['createdBy' => $log->userId]);
                }
            });
    }

    public static function displayNameForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->personnel?->name ?? $user->name;
    }

    public static function displayNameForQuote(Quote $quote, array $auditFallbackMap = []): ?string
    {
        $fromUser = self::displayNameForUser($quote->createdByUser);
        if ($fromUser) {
            return $fromUser;
        }

        $fallback = $auditFallbackMap[(string) $quote->id] ?? null;
        if ($fallback) {
            return $fallback;
        }

        return $quote->personnel?->name;
    }
}
