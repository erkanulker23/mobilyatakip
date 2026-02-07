<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = 'createdAt';
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
