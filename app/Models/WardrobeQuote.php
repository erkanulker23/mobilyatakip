<?php

namespace App\Models;

class WardrobeQuote extends BaseModel
{
    protected $table = 'wardrobe_quotes';

    protected $fillable = [
        'customerName',
        'phone',
        'note',
        'design',
    ];

    protected $casts = [
        'design' => 'array',
    ];
}
