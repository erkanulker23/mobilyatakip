<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Türkiye VKN (10 hane) veya TCKN (11 hane) algoritmik doğrulama.
 * Gerçek mükellef sorgusu için GİB/NVI sistemleri kullanılmalıdır.
 */
class TurkishTaxId implements ValidationRule
{
    public function __construct(
        private string $type = 'vkn' // 'vkn' | 'tckn'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if (!is_string($value) && !is_numeric($value)) {
            $fail('Geçerli bir numara giriniz.');
            return;
        }
        $value = preg_replace('/\s+/', '', (string) $value);
        if ($this->type === 'vkn') {
            $this->validateVkn($value, $fail);
        } else {
            $this->validateTckn($value, $fail);
        }
    }

    private function validateVkn(string $value, Closure $fail): void
    {
        if (!preg_match('/^\d{10}$/', $value)) {
            $fail('Vergi numarası tam 10 haneli olmalıdır.');
            return;
        }
        $digits = array_map('intval', str_split($value));
        if (count(array_unique($digits)) === 1) {
            $fail('Geçersiz vergi numarası.');
            return;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $tmp = ($digits[$i] + (9 - $i)) % 10;
            $res = ($tmp * (int) pow(2, 9 - $i)) % 9;
            if ($tmp !== 0 && $res === 0) {
                $res = 9;
            }
            $sum += $res;
        }
        $check = (10 - ($sum % 10)) % 10;
        if ($check !== $digits[9]) {
            $fail('Vergi numarası geçersiz (kontrol hanesi hatalı).');
        }
    }

    private function validateTckn(string $value, Closure $fail): void
    {
        if (!preg_match('/^\d{11}$/', $value)) {
            $fail('TC kimlik numarası tam 11 haneli olmalıdır.');
            return;
        }
        $digits = array_map('intval', str_split($value));
        if ($digits[0] === 0) {
            $fail('TC kimlik numarası 0 ile başlayamaz.');
            return;
        }
        $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        $digit10 = ($oddSum * 7 - $evenSum) % 10;
        if ($digit10 < 0) {
            $digit10 += 10;
        }
        if ($digit10 !== $digits[9]) {
            $fail('TC kimlik numarası geçersiz (10. hane hatalı).');
            return;
        }
        $digit11 = array_sum(array_slice($digits, 0, 10)) % 10;
        if ($digit11 !== $digits[10]) {
            $fail('TC kimlik numarası geçersiz (11. hane hatalı).');
        }
    }
}
