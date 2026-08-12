<?php

namespace App\Models;

use App\Support\SaleDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Sale extends BaseModel
{
    protected $table = 'sales';

    protected $fillable = [
        'saleNumber',
        'customerId',
        'branchId',
        'personnelId',
        'kdvIncluded',
        'saleDiscountPercent',
        'grandTotalOverride',
        'quoteId',
        'saleDate',
        'dueDate',
        'deliveredAt',
        'orderStatus',
        'workshopCompletedAt',
        'needsFinalMeasurement',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'paidAmount',
        'notes',
        'drawingFiles',
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
        'saleDiscountPercent' => 'decimal:2',
        'grandTotalOverride' => 'decimal:2',
        'saleDate' => 'date',
        'dueDate' => 'date',
        'deliveredAt' => 'datetime',
        'workshopCompletedAt' => 'datetime',
        'needsFinalMeasurement' => 'boolean',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'isCancelled' => 'boolean',
        'drawingFiles' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchId');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnelId');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quoteId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'saleId');
    }

    public function productionStages(): HasMany
    {
        return $this->hasMany(SaleProductionStage::class, 'saleId')->orderByDesc('actionDate');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'saleId');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SaleActivity::class, 'saleId')->orderBy('createdAt', 'desc');
    }

    /** Bu satışa bağlı müşteri tahsilatları */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'saleId')->orderBy('paymentDate', 'desc');
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

    /** Termin / yapılacak listeleri: teslim edilmemiş siparişler. */
    public function scopePendingDelivery(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('deliveredAt')
                ->where(function (Builder $inner) {
                    $inner->whereNull('orderStatus')
                        ->orWhere('orderStatus', '!=', SaleDelivery::DELIVERED);
                });
        });
    }

    /** Teslim edilmiş siparişler — deliveredAt veya orderStatus ile uyumlu. */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('deliveredAt')
                ->orWhere('orderStatus', SaleDelivery::DELIVERED);
        });
    }
}
