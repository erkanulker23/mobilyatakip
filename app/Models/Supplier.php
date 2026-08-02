<?php

namespace App\Models;

use App\Models\Concerns\HasTurkeyAddress;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Supplier extends BaseModel
{
    use HasTurkeyAddress;

    protected $table = 'suppliers';

    protected static function booted(): void
    {
        parent::booted();
        static::deleting(function (Supplier $supplier) {
            $id = $supplier->getKey();
            $purchaseIds = DB::table('purchases')->where('supplierId', $id)->pluck('id');
            if ($purchaseIds->isNotEmpty()) {
                DB::table('supplier_payments')->whereIn('purchaseId', $purchaseIds)->update(['purchaseId' => null]);
                DB::table('purchase_items')->whereIn('purchaseId', $purchaseIds)->delete();
                DB::table('purchases')->where('supplierId', $id)->delete();
            }
            DB::table('supplier_payments')->where('supplierId', $id)->delete();
            DB::table('supplier_statements')->where('supplierId', $id)->delete();
            DB::table('xml_feeds')->where('supplierId', $id)->update(['supplierId' => null]);
        });
    }

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'cityId',
        'districtId',
        'taxNumber',
        'taxOffice',
        'marginPercent',
        'isActive',
        'externalId',
        'externalSource',
    ];

    protected $casts = [
        'marginPercent' => 'decimal:2',
        'isActive' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplierId');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplierId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplierId');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(SupplierStatement::class, 'supplierId');
    }
}
