<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends BaseModel
{
    protected $table = 'customer_payments';

    protected $fillable = [
        'customerId',
        'kasaId',
        'amount',
        'paymentDate',
        'paymentType',
        'reference',
        'notes',
        'saleId',
        'supplierId',
        'linkedSupplierPaymentId',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paymentDate' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function linkedSupplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'linkedSupplierPaymentId');
    }
}
