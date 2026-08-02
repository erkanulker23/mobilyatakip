<?php

namespace App\Rules;

use App\Support\CustomerPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueCustomerPhone implements ValidationRule
{
    public function __construct(private ?string $exceptCustomerId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        $owner = CustomerPhone::findOwner((string) $value, $this->exceptCustomerId);
        if ($owner) {
            $fail("Bu telefon numarası zaten «{$owner->name}» müşterisine kayıtlı.");
        }
    }
}
