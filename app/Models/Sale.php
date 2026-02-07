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
        'kdvIncluded',
        'quoteId',
        'saleDate',
        'dueDate',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'notes',
        'isCancelled',
    ];

    protected $casts = [
        'kdvIncluded' => 'boolean',
        'saleDate' => 'date',
        'dueDate' => 'date',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'isCancelled' => 'boolean',
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
}
