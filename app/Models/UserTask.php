<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTask extends BaseModel
{
    protected $table = 'user_tasks';

    protected $fillable = [
        'userId',
        'personnelId',
        'title',
        'notes',
        'dueDate',
        'color',
        'isCompleted',
        'completedAt',
        'sortOrder',
    ];

    protected $casts = [
        'dueDate' => 'date',
        'isCompleted' => 'boolean',
        'completedAt' => 'datetime',
        'sortOrder' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnelId');
    }
}
