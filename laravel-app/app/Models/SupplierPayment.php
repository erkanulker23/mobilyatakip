<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends BaseModel
{
    protected $table = 'supplier_payments';

    protected $fillable = [
        'supplierId',
        'kasaId',
        'amount',
        'paymentDate',
        'paymentType',
        'reference',
        'notes',
        'purchaseId',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paymentDate' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchaseId');
    }
}
