<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'saleId',
        'serviceTicketId',
        'paymentFor',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paymentDate' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (ShippingCompanyPayment $payment) {
            $payment->sales()->detach();
            $payment->serviceTickets()->detach();
        });
    }

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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(
            Sale::class,
            'shipping_company_payment_sales',
            'shippingCompanyPaymentId',
            'saleId',
        );
    }

    public function serviceTicket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'serviceTicketId');
    }

    public function serviceTickets(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceTicket::class,
            'shipping_company_payment_service_tickets',
            'shippingCompanyPaymentId',
            'serviceTicketId',
        );
    }
}
