<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasaHareket extends BaseModel
{
    protected $table = 'kasa_hareket';

    protected $fillable = [
        'type',
        'amount',
        'movementDate',
        'description',
        'kasaId',
        'fromKasaId',
        'toKasaId',
        'createdBy',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'movementDate' => 'date',
    ];

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function fromKasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'fromKasaId');
    }

    public function toKasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'toKasaId');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy');
    }
}
