<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::query()->orderBy('name')->get()->map(function ($row) {
            return [
                $row->name,
                $row->email,
                $row->phone,
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
            'Ad',
            'E-posta',
            'Telefon',
            'Adres',
            'TC Kimlik No',
            'Vergi No',
            'Vergi Dairesi',
            'Aktif (1/0)',
        ];
    }
}
