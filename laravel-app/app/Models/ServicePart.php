<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePart extends BaseModel
{
    protected $table = 'service_parts';

    protected $fillable = [
        'detailId',
        'productId',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(ServiceTicketDetail::class, 'detailId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
