<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPiketExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $records;
    protected $periodeText;
    protected $filters;
    protected $settingan;
    private $rowNumber = 0;

    public function __construct($records, $periodeText = null, $filters = null, $settingan = null)
    {
        $this->records = $records;
        $this->periodeText = $periodeText;
        $this->filters = $filters;
        $this->settingan = $settingan;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->records instanceof \Illuminate\Support\Enumerable 
            ? $this->records 
            : collect($this->records);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Nama Guru Piket (Petugas)',
            'Titik Piket',
            'Nama Guru Mengajar (Di Kelas)',
            'Mata Pelajaran',
            'Kelas & Jurusan',
            'Jam Pelajaran',
            'Status Kehadiran',
            'Terlambat',
            'Keterangan'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $jp = $row->jadwal_piket ?? $row->jadwalPiket ?? null;
        $jm = $row->jadwal_mapel ?? $row->jadwalMapel ?? null;

        $jamPelajaranStr = '-';
        if ($jm && $jm->jamPelajarans && $jm->jamPelajarans->count() > 0) {
            $jamPelajaranStr = $jm->jamPelajarans->pluck('urutan_jam')->join(', ');
        } elseif ($jp && $jp->jamPelajarans && $jp->jamPelajarans->count() > 0) {
            $jamPelajaranStr = $jp->jamPelajarans->pluck('urutan_jam')->join(', ');
        }

        $kelasJurusan = '-';
        if ($jm && $jm->kelas) {
            $kelasJurusan = $jm->kelas->nama_kelas;
            if ($jm->kelas->jurusan) {
                $kelasJurusan .= ' (' . $jm->kelas->jurusan->nama_jurusan . ')';
            }
        }

        $statusKehadiran = $row->status_kehadiran ?? '-';
        if (!empty($row->is_piket_petugas)) {
            $statusKehadiran .= ' (Piket)';
        }

        $tanggalStr = '-';
        if (!empty($jp->tanggal_piket)) {
            try {
                $tanggalStr = \Carbon\Carbon::parse($jp->tanggal_piket)->format('d/m/Y');
            } catch (\Exception $e) {
                $tanggalStr = $jp->tanggal_piket;
            }
        }

        return [
            $this->rowNumber,
            $tanggalStr,
            $jp->guruPiket->name ?? '-',
            $jp->titik->nama_titik ?? '-',
            $jm?->guru?->name ?? '-',
            $jm?->mataPelajaran?->nama_mapel ?? '-',
            $kelasJurusan,
            $jamPelajaranStr,
            $statusKehadiran,
            (!empty($row->is_terlambat)) ? 'Ya' : 'Tidak',
            $row->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'], // Dark slate
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap Piket';
    }
}
