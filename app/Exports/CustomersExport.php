<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CustomersExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    public function collection()
    {
        return Customer::query()->orderBy('name')->get()->map(function ($row) {
            return [
                $row->id,
                $row->name,
                $row->email,
                $row->phone,
                $row->phone2,
                $row->address,
                $row->identityNumber,
                $row->taxNumber,
                $row->taxOffice,
                $row->isActive ? 1 : 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Ad',
            'E-posta',
            'Telefon',
            'Telefon 2',
            'Adres',
            'TC Kimlik No',
            'Vergi No',
            'Vergi Dairesi',
            'Aktif (1/0)',
        ];
    }
}
