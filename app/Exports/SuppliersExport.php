<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuppliersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Supplier::query()->orderBy('name')->get()->map(function ($row) {
            return [
                $row->name,
                $row->email,
                $row->phone,
                $row->address,
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
            'Vergi No',
            'Vergi Dairesi',
            'Aktif (1/0)',
        ];
    }
}
