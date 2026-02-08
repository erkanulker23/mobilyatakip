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
        'warehouseId',
        'shippingCompanyId',
        'vehiclePlate',
        'driverName',
        'driverPhone',
        'kdvIncluded',
        'supplierDiscountRate',
        'purchaseDate',
        'dueDate',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'isReturn',
        'notes',
        'isCancelled',
        'efaturaUuid',
        'efaturaStatus',
        'efaturaSentAt',
        'efaturaEnvelopeId',
        'efaturaResponse',
    ];

    protected $casts = [
        'efaturaSentAt' => 'datetime',
        'kdvIncluded' => 'boolean',
        'supplierDiscountRate' => 'decimal:2',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouseId');
    }

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shippingCompanyId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchaseId');
    }
}
