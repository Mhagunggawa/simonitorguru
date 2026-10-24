<?php

namespace App\Exports;

use App\Models\RekapKehadiran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Http\Request;

class LaporanPiketExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        $query = RekapKehadiran::whereNotNull('jadwal_piket_id')
            ->with(['jadwalPiket.guruPiket', 'jadwalPiket.titik', 'jadwalMapel.guru', 'jadwalMapel.mataPelajaran', 'jadwalMapel.kelas.jurusan', 'jadwalMapel.jamPelajarans']);

        if ($this->request->filled('periode')) {
            $periode = $this->request->input('periode');
            
            if ($periode === 'bulanan' && $this->request->filled('bulan')) {
                // format YYYY-MM
                $bulan = $this->request->input('bulan');
                $parts = explode('-', $bulan);
                if (count($parts) === 2) {
                    $query->whereHas('jadwalPiket', function($q) use ($parts) {
                        $q->whereYear('tanggal_piket', $parts[0])
                          ->whereMonth('tanggal_piket', $parts[1]);
                    });
                }
            } else if ($periode === 'semester' && $this->request->filled('semester') && $this->request->filled('tahun_ajaran')) {
                $semester = $this->request->input('semester');
                $tahunAjaran = $this->request->input('tahun_ajaran');
                
                if ($semester === 'ganjil') {
                    $startDate = \Carbon\Carbon::create($tahunAjaran, 7, 1)->startOfDay();
                    $endDate = \Carbon\Carbon::create($tahunAjaran, 12, 31)->endOfDay();
                } else {
                    $startDate = \Carbon\Carbon::create($tahunAjaran + 1, 1, 1)->startOfDay();
                    $endDate = \Carbon\Carbon::create($tahunAjaran + 1, 6, 30)->endOfDay();
                }

                $query->whereHas('jadwalPiket', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal_piket', [$startDate, $endDate]);
                });
            }
        } else {
            // Fallback for regular laporan which uses start_date and end_date
            if ($this->request->filled('start_date')) {
                $query->whereHas('jadwalPiket', function($q) {
                    $q->whereDate('tanggal_piket', '>=', $this->request->input('start_date'));
                });
            }

            if ($this->request->filled('end_date')) {
                $query->whereHas('jadwalPiket', function($q) {
                    $q->whereDate('tanggal_piket', '<=', $this->request->input('end_date'));
                });
            }
        }

        if ($this->request->filled('search')) {
            $search = $this->request->input('search');
            $query->where(function($sub) use ($search) {
                $sub->whereHas('jadwalPiket.guruPiket', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('jadwalMapel.guru', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('jadwalMapel.mataPelajaran', function($q) use ($search) {
                    $q->where('nama_mapel', 'like', "%{$search}%");
                })->orWhereHas('jadwalMapel.kelas', function($q) use ($search) {
                    $q->where('nama_kelas', 'like', "%{$search}%");
                });
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
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
        $jamPelajaranStr = '-';
        if ($row->jadwalMapel && $row->jadwalMapel->jamPelajarans) {
            $jamPelajaranStr = $row->jadwalMapel->jamPelajarans->pluck('urutan_jam')->join(', ');
        }

        $kelasJurusan = '-';
        if ($row->jadwalMapel && $row->jadwalMapel->kelas) {
            $kelasJurusan = $row->jadwalMapel->kelas->nama_kelas;
            if ($row->jadwalMapel->kelas->jurusan) {
                $kelasJurusan .= ' (' . $row->jadwalMapel->kelas->jurusan->nama_jurusan . ')';
            }
        }

        return [
            $row->jadwalPiket->tanggal_piket ?? '-',
            $row->jadwalPiket->guruPiket->name ?? '-',
            $row->jadwalPiket->titik->nama_titik ?? '-',
            $row->jadwalMapel->guru->name ?? '-',
            $row->jadwalMapel->mataPelajaran->nama_mapel ?? '-',
            $kelasJurusan,
            $jamPelajaranStr,
            $row->status_kehadiran ?? '-',
            $row->is_terlambat ? 'Ya' : 'Tidak',
            $row->keterangan ?? '-'
        ];
    }
}
