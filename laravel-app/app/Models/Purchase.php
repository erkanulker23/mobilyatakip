<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends BaseModel
{
    protected $table = 'purchases';

    protected $fillable = [
        'purchaseNumber',
        'supplierId',
        'purchaseDate',
        'dueDate',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'isReturn',
        'notes',
    ];

    protected $casts = [
        'purchaseDate' => 'date',
        'dueDate' => 'date',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'isReturn' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchaseId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'purchaseId');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->grandTotal - (float) $this->paidAmount);
    }
}
