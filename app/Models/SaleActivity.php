<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleActivity extends BaseModel
{
    protected $table = 'sale_activities';

    const TYPE_CREATED = 'created';
    const TYPE_SUPPLIER_EMAIL_SENT = 'supplier_email_sent';
    const TYPE_SUPPLIER_EMAIL_READ = 'supplier_email_read';
    const TYPE_SUPPLIER_EMAIL_REPLIED = 'supplier_email_replied';

    protected $fillable = [
        'saleId',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'createdAt' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }
}
