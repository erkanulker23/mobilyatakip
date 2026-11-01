<?php

namespace App\Models;

class WardrobeProject extends BaseModel
{
    protected $table = 'wardrobe_projects';

    protected $fillable = [
        'token',
        'userId',
        'customerName',
        'phone',
        'projectName',
        'note',
        'design',
    ];

    protected $casts = [
        'design' => 'array',
    ];
}
