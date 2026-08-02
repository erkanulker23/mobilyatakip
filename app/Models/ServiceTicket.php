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
        'reportedProblems',
        'assignedUserId',
        'assignedVehiclePlate',
        'assignedDriverName',
        'assignedDriverPhone',
        'shippingCompanyId',
        'shippingVehicleId',
        'openedAt',
        'dueDate',
        'closedAt',
        'notes',
        'serviceChargeAmount',
        'images',
    ];

    protected $casts = [
        'underWarranty' => 'boolean',
        'openedAt' => 'datetime',
        'dueDate' => 'date',
        'closedAt' => 'datetime',
        'serviceChargeAmount' => 'decimal:2',
        'images' => 'array',
        'reportedProblems' => 'array',
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

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shippingCompanyId');
    }

    public function shippingVehicle(): BelongsTo
    {
        return $this->belongsTo(ShippingCompanyVehicle::class, 'shippingVehicleId');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ServiceTicketDetail::class, 'ticketId');
    }
}
