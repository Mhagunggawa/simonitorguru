<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PegawaiAdministrasiTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Nama Pegawai',
        ];
    }

    public function array(): array
    {
        return [
            ['Ahmad Subagio, S.Pd.'],
            ['Dewi Lestari, S.E.'],
            ['Bambang Irawan'],
        ];
    }
}

