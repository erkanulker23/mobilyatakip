<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends BaseModel
{
    protected $table = 'sales';

    protected $fillable = [
        'saleNumber',
        'customerId',
        'quoteId',
        'saleDate',
        'dueDate',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'notes',
    ];

    protected $casts = [
        'saleDate' => 'date',
        'dueDate' => 'date',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quoteId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'saleId');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'saleId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'saleId');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->grandTotal - (float) $this->paidAmount);
    }
}
