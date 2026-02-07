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
        'kdvIncluded',
        'purchaseDate',
        'dueDate',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'isReturn',
        'notes',
        'isCancelled',
    ];

    protected $casts = [
        'kdvIncluded' => 'boolean',
        'purchaseDate' => 'date',
        'dueDate' => 'date',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'isReturn' => 'boolean',
        'isCancelled' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchaseId');
    }
}
