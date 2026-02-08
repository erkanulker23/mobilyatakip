<?php

namespace App\Imports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuppliersImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Supplier
    {
        $name = $this->trim($row['ad'] ?? $row['name'] ?? '');
        if ($name === '') {
            return null;
        }

        return new Supplier([
            'code' => $this->trim($row['kod'] ?? $row['code'] ?? null),
            'name' => $name,
            'email' => $this->trim($row['e_posta'] ?? $row['email'] ?? null),
            'phone' => $this->trim($row['telefon'] ?? $row['phone'] ?? null),
            'address' => $this->trim($row['adres'] ?? $row['address'] ?? null),
            'taxNumber' => $this->trim($row['vergi_no'] ?? $row['tax_number'] ?? null),
            'taxOffice' => $this->trim($row['vergi_dairesi'] ?? $row['tax_office'] ?? null),
            'isActive' => $this->parseBool($row['aktif_1_0'] ?? $row['is_active'] ?? true),
        ]);
    }

    public function rules(): array
    {
        return [
            'kod' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:50',
            'ad' => 'nullable|string',
            'e_posta' => 'nullable|email',
            'telefon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'adres' => 'nullable|string',
            'vergi_no' => 'nullable|string',
            'vergi_dairesi' => 'nullable|string',
            'aktif_1_0' => 'nullable',
        ];
    }

    private function trim($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    private function parseBool($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));
        return in_array($v, ['1', 'true', 'evet', 'aktif', 'yes'], true);
    }
}
