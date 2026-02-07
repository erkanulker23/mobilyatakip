<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicket extends BaseModel
{
    protected $table = 'service_tickets';

    protected $fillable = [
        'ticketNumber',
        'saleId',
        'customerId',
        'status',
        'underWarranty',
        'issueType',
        'description',
        'assignedUserId',
        'assignedVehiclePlate',
        'assignedDriverName',
        'assignedDriverPhone',
        'openedAt',
        'closedAt',
        'notes',
        'serviceChargeAmount',
        'images',
    ];

    protected $casts = [
        'underWarranty' => 'boolean',
        'openedAt' => 'datetime',
        'closedAt' => 'datetime',
        'serviceChargeAmount' => 'decimal:2',
        'images' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignedUserId');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ServiceTicketDetail::class, 'ticketId');
    }
}
