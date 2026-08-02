<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    public function log(string $entity, ?string $entityId, string $action, ?array $oldValue = null, ?array $newValue = null): void
    {
        try {
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'userId' => auth()->id(),
                'entity' => $entity,
                'entityId' => $entityId,
                'action' => $action,
                'oldValue' => $this->withActor($oldValue),
                'newValue' => $this->withActor($newValue),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @param  array<string, mixed>|null  $payload */
    private function withActor(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $user = auth()->user();
        if ($user && empty($payload['_actorName'])) {
            $payload['_actorName'] = $user->name;
        }

        return $payload;
    }

    public function logCreate(string $entity, string $entityId, array $data): void
    {
        $this->log($entity, $entityId, 'create', null, $data);
    }

    public function logUpdate(string $entity, string $entityId, array $oldData, array $newData): void
    {
        $this->log($entity, $entityId, 'update', $oldData, $newData);
    }

    public function logDelete(string $entity, string $entityId, array $oldData): void
    {
        $this->log($entity, $entityId, 'delete', $oldData, null);
    }

    public function logCancel(string $entity, string $entityId): void
    {
        $this->log($entity, $entityId, 'cancel', null, ['isCancelled' => true]);
    }

    public function logAction(string $entity, ?string $entityId, string $action, array $data = []): void
    {
        $this->log($entity, $entityId, $action, null, $data);
    }
}
