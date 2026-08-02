<?php

namespace App\Models;

use App\Models\Concerns\HasTurkeyAddress;

class Company extends BaseModel
{
    use HasTurkeyAddress;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'appName',
        'address',
        'cityId',
        'districtId',
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

    /** Logo görüntüleme URL'si (storage symlink sorunlarında Laravel üzerinden servis edilir) */
    public function logoDisplayUrl(): ?string
    {
        if (!$this->logoUrl) {
            return null;
        }

        return storage_url($this->logoUrl);
    }
}
