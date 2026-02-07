<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlFeed extends BaseModel
{
    const UPDATED_AT = null;

    protected $table = 'xml_feeds';

    protected $fillable = [
        'name',
        'url',
        'supplierId',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }
}
