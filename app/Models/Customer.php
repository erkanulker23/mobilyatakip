<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected static function booted(): void
    {
        static::deleting(function (Customer $customer) {
            $id = $customer->getKey();
            $quoteIds = DB::table('quotes')->where('customerId', $id)->pluck('id');
            if ($quoteIds->isNotEmpty()) {
                DB::table('sales')->whereIn('quoteId', $quoteIds)->update(['quoteId' => null]);
                DB::table('quote_items')->whereIn('quoteId', $quoteIds)->delete();
                DB::table('quotes')->where('customerId', $id)->delete();
            }
            DB::table('customer_payments')->where('customerId', $id)->delete();
            $saleIds = DB::table('sales')->where('customerId', $id)->pluck('id');
            if ($saleIds->isNotEmpty()) {
                DB::table('sale_activities')->whereIn('saleId', $saleIds)->delete();
                DB::table('sale_items')->whereIn('saleId', $saleIds)->delete();
                DB::table('service_tickets')->whereIn('saleId', $saleIds)->update(['saleId' => null]);
                DB::table('sales')->where('customerId', $id)->delete();
            }
            DB::table('service_tickets')->where('customerId', $id)->update(['customerId' => null]);
        });
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'taxNumber',
        'taxOffice',
        'identityNumber',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'customerId');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customerId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'customerId');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'customerId');
    }
}
