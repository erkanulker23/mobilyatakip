<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCompanyPayment extends BaseModel
{
    protected $table = 'shipping_company_payments';

    protected $fillable = [
        'shippingCompanyId',
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

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shippingCompanyId');
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
