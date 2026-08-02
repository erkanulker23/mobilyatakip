<?php

namespace App\Imports;

use App\Models\Customer;
use App\Support\CustomerPhone;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $line = $i + 2; // başlık satırı 1. satır
            $row = $row->toArray();

            $name = $this->trim($row['ad'] ?? $row['name'] ?? null);
            if ($name === null) {
                // Tamamen boş satırları sessizce atla, kısmen dolu olanları bildir
                if (collect($row)->filter(fn ($v) => $this->trim($v) !== null)->isNotEmpty()) {
                    $this->errors[] = "Satır {$line}: Ad boş olduğu için atlandı.";
                    $this->skipped++;
                }
                continue;
            }

            $data = [
                'name' => $name,
                'email' => $this->email($row['e_posta'] ?? $row['email'] ?? null),
                'phone' => $this->phone($row['telefon'] ?? $row['phone'] ?? null),
                'phone2' => $this->phone($row['telefon_2'] ?? $row['phone2'] ?? null),
                'address' => $this->trim($row['adres'] ?? $row['address'] ?? null),
                'identityNumber' => $this->digits($row['tc_kimlik_no'] ?? $row['identity_number'] ?? null, 11),
                'taxNumber' => $this->digits($row['vergi_no'] ?? $row['tax_number'] ?? null, 10),
                'taxOffice' => $this->trim($row['vergi_dairesi'] ?? $row['tax_office'] ?? null),
                'isActive' => $this->parseBool($row['aktif_1_0'] ?? $row['is_active'] ?? true),
            ];

            $existing = $this->findExisting($row, $data);

            if ($existing) {
                $phoneErrors = CustomerPhone::duplicateFieldErrors(
                    $data['phone'] ?? $existing->phone,
                    $data['phone2'] ?? $existing->phone2,
                    $existing->id
                );
                if ($phoneErrors !== []) {
                    $this->errors[] = 'Satır '.$line.': '.implode(' ', $phoneErrors);
                    $this->skipped++;
                    continue;
                }
                // Excel'de boş bırakılan alanlar mevcut veriyi silmesin
                $existing->fill(array_filter($data, fn ($v) => $v !== null && $v !== ''));
                $existing->isActive = $data['isActive'];
                $existing->save();
                $this->updated++;
            } else {
                $phoneErrors = CustomerPhone::duplicateFieldErrors($data['phone'], $data['phone2']);
                if ($phoneErrors !== []) {
                    $this->errors[] = 'Satır '.$line.': '.implode(' ', $phoneErrors);
                    $this->skipped++;
                    continue;
                }
                Customer::create($data);
                $this->created++;
            }
        }
    }

    private function findExisting(array $row, array $data): ?Customer
    {
        $id = $this->trim($row['id'] ?? null);
        if ($id !== null) {
            $byId = Customer::find($id);
            if ($byId) {
                return $byId;
            }
        }

        foreach (['identityNumber', 'taxNumber', 'email'] as $key) {
            if (! empty($data[$key])) {
                $match = Customer::where($key, $data[$key])->first();
                if ($match) {
                    return $match;
                }
            }
        }

        if (! empty($data['phone'])) {
            $match = CustomerPhone::findOwner($data['phone']);
            if ($match) {
                return $match;
            }
        }

        if (! empty($data['phone2'])) {
            $match = CustomerPhone::findOwner($data['phone2']);
            if ($match) {
                return $match;
            }
        }

        return Customer::where('name', $data['name'])->first();
    }

    private function trim($value): ?string
    {
        if ($value === null || is_bool($value)) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    private function email($value): ?string
    {
        $s = $this->trim($value);
        return $s !== null && filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null;
    }

    /**
     * Excel telefonları sayı olarak okuyabildiği için (baştaki 0 kaybolur)
     * sadece rakamları alıp 10 haneli numaraların başına 0 ekliyoruz.
     */
    private function phone($value): ?string
    {
        $s = $this->trim($value);
        if ($s === null) {
            return null;
        }
        // Metin olarak gelmişse kullanıcının yazdığı biçimi koru
        if (str_starts_with($s, '0') || str_starts_with($s, '+')) {
            return substr($s, 0, 20);
        }
        $digits = preg_replace('/\D/', '', $s);
        if ($digits === '') {
            return null;
        }
        // Excel sayı olarak okuduysa baştaki 0 kaybolmuştur, geri ekle
        if (strlen($digits) === 10) {
            $digits = '0' . $digits;
        }
        return substr($digits, 0, 20);
    }

    private function digits($value, int $length): ?string
    {
        $s = $this->trim($value);
        if ($s === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $s);
        return strlen($digits) === $length ? $digits : null;
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
