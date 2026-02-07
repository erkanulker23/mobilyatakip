<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
        'efaturaUuid',
        'efaturaStatus',
        'efaturaSentAt',
        'efaturaEnvelopeId',
        'efaturaResponse',
    ];

    protected $casts = [
        'efaturaSentAt' => 'datetime',
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

    public function activities(): HasMany
    {
        return $this->hasMany(SaleActivity::class, 'saleId')->orderBy('createdAt', 'desc');
    }

    /** Satış kalemlerindeki ürünlerin tedarikçilerini (e-posta adresi olan) benzersiz döner */
    public function getSuppliersWithEmail(): Collection
    {
        $suppliers = collect();
        foreach ($this->items ?? [] as $item) {
            $product = $item->product;
            if (!$product || !$product->supplierId) {
                continue;
            }
            $supplier = $product->supplier;
            if ($supplier && $supplier->email && $supplier->isActive) {
                $suppliers[$supplier->id] = $supplier;
            }
        }
        return $suppliers->values();
    }

    /** Tedarikçiye e-posta gönderildi mi? */
    public function hasSupplierEmailSent(): bool
    {
        return $this->activities()->where('type', SaleActivity::TYPE_SUPPLIER_EMAIL_SENT)->exists();
    }
}
