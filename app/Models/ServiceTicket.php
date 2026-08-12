<?php

namespace App\Models;

use App\Support\ServiceTicketStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceTicket extends BaseModel
{
    protected $table = 'service_tickets';

    protected $fillable = [
        'ticketNumber',
        'saleId',
        'customerId',
        'branchId',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchId');
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

    /** Kaydı açan kullanıcı (ilk "acildi" işlem kaydı). */
    public function openingDetail(): HasOne
    {
        return $this->hasOne(ServiceTicketDetail::class, 'ticketId')
            ->ofMany(
                ['actionDate' => 'min'],
                fn ($query) => $query->where('action', 'acildi')
            );
    }

    /** Kaydı kapatan kullanıcı (son "kapatildi" işlem kaydı). */
    public function closingDetail(): HasOne
    {
        return $this->hasOne(ServiceTicketDetail::class, 'ticketId')
            ->ofMany(
                ['actionDate' => 'max'],
                fn ($query) => $query->where('action', 'kapatildi')
            );
    }

    /** Son "atölyede iş bitti" kaydı. */
    public function workshopFinishedDetail(): HasOne
    {
        return $this->hasOne(ServiceTicketDetail::class, 'ticketId')
            ->ofMany(
                ['actionDate' => 'max'],
                fn ($query) => $query->where('action', ServiceTicketStatus::ACTION_WORKSHOP_FINISHED)
            );
    }

    public function isWorkshopFinished(): bool
    {
        if ($this->relationLoaded('workshopFinishedDetail')) {
            return $this->workshopFinishedDetail !== null;
        }

        return $this->details()
            ->where('action', ServiceTicketStatus::ACTION_WORKSHOP_FINISHED)
            ->exists();
    }

    /** Eski kayıtlar: listeden durum değiştirilerek kapatıldığında oluşan kayıt. */
    public function legacyClosingDetail(): HasOne
    {
        return $this->hasOne(ServiceTicketDetail::class, 'ticketId')
            ->ofMany(
                ['actionDate' => 'max'],
                fn ($query) => $query->where('action', 'durum_guncelleme')
                    ->where(function ($q) {
                        $q->where('notes', 'Durum: Tamamlandı')
                            ->orWhere('notes', 'Durum: İptal');
                    })
            );
    }

    public function closedByUserName(): ?string
    {
        if (! ServiceTicketStatus::isClosed($this->status ?? '')) {
            return null;
        }

        return $this->closingDetail?->user?->name
            ?? $this->legacyClosingDetail?->user?->name;
    }
}
