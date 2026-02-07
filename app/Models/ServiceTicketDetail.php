<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicketDetail extends BaseModel
{
    protected $table = 'service_ticket_details';

    protected $fillable = [
        'ticketId',
        'userId',
        'action',
        'actionDate',
        'notes',
        'images',
    ];

    protected $casts = [
        'actionDate' => 'datetime',
        'images' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'ticketId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ServicePart::class, 'detailId');
    }
}
