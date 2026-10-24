<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanTindakLanjutExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Nama Guru',
            'Tipe Guru',
            'Total Alpha',
            'Status Tindak Lanjut',
            'Riwayat SP yang Diterbitkan',
            'Tanggal Penerbitan Terakhir'
        ];
    }

    public function map($row): array
    {
        $spHistory = $row['sp_history'] ?? [];
        $spList = collect($spHistory)->pluck('tipe_peringatan')->join(', ');
        $lastSpDate = !empty($spHistory) ? (end($spHistory)['created_at'] ?? '-') : '-';
        if ($lastSpDate !== '-' && is_string($lastSpDate)) {
            try {
                $lastSpDate = \Carbon\Carbon::parse($lastSpDate)->format('d/m/Y H:i');
            } catch (\Exception $e) {}
        }

        $status = 'Disiplin';
        $totalAlpha = $row['total_alpha'] ?? 0;
        if (!empty($spList)) {
            $status = 'Terkena ' . $spList;
        } elseif ($totalAlpha === 1) {
            $status = 'Teguran Lisan';
        } elseif ($totalAlpha >= 2) {
            $status = 'Perlu Tindakan';
        }

        return [
            $row['nama'] ?? '-',
            $row['tipe_guru'] ?? '-',
            $totalAlpha . 'x Alpha',
            $status,
            $spList ?: '-',
            $lastSpDate ?: '-'
        ];
    }
}
