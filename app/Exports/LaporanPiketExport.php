<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPiketExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $records;
    protected $periodeText;
    protected $filters;
    protected $settingan;

    public function __construct($records, $periodeText = null, $filters = null, $settingan = null)
    {
        $this->records = $records;
        $this->periodeText = $periodeText;
        $this->filters = $filters;
        $this->settingan = $settingan;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        $rows = collect();
        $sessionNumber = 0;

        foreach ($this->records as $session) {
            $sessionNumber++;
            $jp = $session->jadwal_piket ?? null;
            $tanggalStr = '-';
            if (!empty($jp?->tanggal_piket)) {
                try {
                    $tanggalStr = \Carbon\Carbon::parse($jp->tanggal_piket)->format('d/m/Y');
                } catch (\Exception $e) {
                    $tanggalStr = $jp->tanggal_piket;
                }
            }

            $guruPiket = $jp?->guruPiket?->name ?? ($jp?->guru_piket?->name ?? '-');
            $titik = $jp?->titik?->nama_titik ?? '-';
            $jamStr = $session->jam_str ?? '-';
            $ringkasanSesi = $session->ringkasan_teks ?? '-';
            $isTerlambat = !empty($session->is_terlambat) ? 'Ya' : 'Tidak';

            if (!empty($session->is_piket_petugas)) {
                // Petugas piket izin / sakit / alpha (tanpa pemantauan kelas)
                $rows->push([
                    'no' => $sessionNumber,
                    'tanggal' => $tanggalStr,
                    'jam' => $jamStr,
                    'guru_piket' => $guruPiket,
                    'titik' => $titik,
                    'ringkasan' => "Petugas Piket {$session->status_kehadiran}",
                    'kelas' => '-',
                    'mapel' => '-',
                    'guru_mengajar' => '-',
                    'status_kehadiran' => $session->status_kehadiran . ' (Piket)',
                    'terlambat' => 'Tidak',
                    'keterangan' => $session->keterangan ?? '-'
                ]);
            } else {
                // Sesi pemantauan dengan rincian kelas
                foreach ($session->classes as $c) {
                    $kelasJurusan = $c->kelas_nama;
                    if (!empty($c->jurusan_nama)) {
                        $kelasJurusan .= ' (' . $c->jurusan_nama . ')';
                    }

                    $rows->push([
                        'no' => $sessionNumber,
                        'tanggal' => $tanggalStr,
                        'jam' => $jamStr,
                        'guru_piket' => $guruPiket,
                        'titik' => $titik,
                        'ringkasan' => $ringkasanSesi,
                        'kelas' => $kelasJurusan,
                        'mapel' => $c->mapel_nama ?? '-',
                        'guru_mengajar' => $c->guru_nama ?? '-',
                        'status_kehadiran' => $c->status_kehadiran ?? '-',
                        'terlambat' => $isTerlambat,
                        'keterangan' => $c->keterangan ?? '-'
                    ]);
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No Sesi',
            'Tanggal',
            'Jam Pelajaran',
            'Guru Piket (Petugas)',
            'Titik Piket',
            'Ringkasan Sesi',
            'Kelas & Jurusan',
            'Mata Pelajaran',
            'Guru Mengajar (Di Kelas)',
            'Status Kehadiran',
            'Terlambat',
            'Keterangan'
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
        return 'Laporan Piket Per Sesi';
    }
}
