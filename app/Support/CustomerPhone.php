<?php

namespace App\Support;

use App\Models\Customer;

final class CustomerPhone
{
    /** Karşılaştırma için telefonu normalize eder (son 10 hane). */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 11) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    /** Numara başka bir müşterinin phone veya phone2 alanında kayıtlı mı? */
    public static function findOwner(?string $phone, ?string $exceptCustomerId = null): ?Customer
    {
        $key = self::normalize($phone);
        if ($key === null) {
            return null;
        }

        $query = Customer::query()->select(['id', 'name', 'phone', 'phone2']);

        if ($exceptCustomerId !== null) {
            $query->where('id', '!=', $exceptCustomerId);
        }

        foreach ($query->cursor() as $customer) {
            if (self::normalize($customer->phone) === $key || self::normalize($customer->phone2) === $key) {
                return $customer;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public static function duplicateFieldErrors(?string $phone, ?string $phone2, ?string $exceptCustomerId = null): array
    {
        $errors = [];

        $phoneKey = self::normalize($phone);
        $phone2Key = self::normalize($phone2);

        if ($phoneKey !== null && $phone2Key !== null && $phoneKey === $phone2Key) {
            $errors['phone2'] = 'Telefon 1 ve Telefon 2 aynı numara olamaz.';
        }

        foreach (['phone' => $phone, 'phone2' => $phone2] as $field => $value) {
            $owner = self::findOwner($value, $exceptCustomerId);
            if ($owner) {
                $errors[$field] = "Bu telefon numarası zaten «{$owner->name}» müşterisine kayıtlı.";
            }
        }

        return $errors;
    }
}
