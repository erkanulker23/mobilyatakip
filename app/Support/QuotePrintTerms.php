<?php

namespace App\Support;

final class QuotePrintTerms
{
    /** @return list<string> */
    public static function items(): array
    {
        return [
            'Sipariş bedelinin %50\'si peşin, kalan %50\'si teslimat ve montaj tamamlandığında tahsil edilir.',
            'Teklif, düzenlendiği tarihten itibaren 3 gün geçerlidir.',
            'Üretim, ön ödeme ve nihai proje onayının ardından başlar.',
            'Sipariş sonrası proje değişiklikleri ek ücret ve süre değişikliğine neden olabilir.',
            'Teslimat süresi sipariş onayında yazılı olarak belirlenir.',
            'Doğal malzemelerde damar, desen ve ton farklılıkları görülebilir.',
            'Sipariş iptal ve iade talepleri, yürürlükteki mevzuata ve üretimin mevcut aşamasına göre değerlendirilir.',
        ];
    }
}
