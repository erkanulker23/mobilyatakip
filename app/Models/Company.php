<?php

namespace App\Models;

class Company extends BaseModel
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'address',
        'taxNumber',
        'taxOffice',
        'phone',
        'email',
        'logoUrl',
        'website',
        'metaTitle',
        'metaDescription',
        'ntgsmUsername',
        'ntgsmPassword',
        'ntgsmOriginator',
        'ntgsmApiUrl',
        'paytrMerchantId',
        'paytrMerchantKey',
        'paytrMerchantSalt',
        'paytrTestMode',
        'mailHost',
        'mailPort',
        'mailUser',
        'mailPassword',
        'mailFrom',
        'mailSecure',
        'efaturaProvider',
        'efaturaEndpoint',
        'efaturaUsername',
        'efaturaPassword',
        'efaturaTestMode',
    ];

    protected $casts = [
        'paytrTestMode' => 'boolean',
        'mailSecure' => 'boolean',
        'mailPort' => 'integer',
        'efaturaTestMode' => 'boolean',
    ];
}
