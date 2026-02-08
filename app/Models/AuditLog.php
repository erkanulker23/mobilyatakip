<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseModel
{
    const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'userId',
        'entity',
        'entityId',
        'action',
        'oldValue',
        'newValue',
        'ipAddress',
        'userAgent',
    ];

    protected $casts = [
        'oldValue' => 'array',
        'newValue' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
