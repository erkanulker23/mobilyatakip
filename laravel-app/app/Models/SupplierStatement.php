<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierStatement extends BaseModel
{
    protected $table = 'supplier_statements';

    protected $fillable = [
        'supplierId',
        'startDate',
        'endDate',
        'openingBalance',
        'totalPurchases',
        'totalPayments',
        'closingBalance',
        'status',
        'pdfUrl',
        'sentAt',
    ];

    protected $casts = [
        'startDate' => 'date',
        'endDate' => 'date',
        'openingBalance' => 'decimal:2',
        'totalPurchases' => 'decimal:2',
        'totalPayments' => 'decimal:2',
        'closingBalance' => 'decimal:2',
        'sentAt' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }
}
