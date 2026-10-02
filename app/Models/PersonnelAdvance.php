<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAdvance extends BaseModel
{
    protected $table = 'personnel_advances';

    protected $fillable = [
        'personnelId',
        'kasaId',
        'amount',
        'advanceDate',
        'period',
        'note',
        'createdBy',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'advanceDate' => 'date',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnelId');
    }

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function periodLabel(): string
    {
        return $this->period === 'aylik' ? 'Aylık' : 'Günlük';
    }

    public function cariLabel(): string
    {
        $name = $this->personnel?->name ?? 'Personel';
        $when = $this->period === 'aylik'
            ? $this->advanceDate?->format('m.Y')
            : $this->advanceDate?->format('d.m.Y');

        return 'Personel avansı · '.$name.' · '.$this->periodLabel().($when ? ' · '.$when : '');
    }
}
