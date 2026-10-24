<?php

namespace App\Exports;

use App\Models\AbsensiGms;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Http\Request;

class LaporanGmsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        $query = AbsensiGms::with('user');

        if ($this->request->filled('periode')) {
            $periode = $this->request->input('periode');
            
            if ($periode === 'bulanan' && $this->request->filled('bulan')) {
                // format YYYY-MM
                $bulan = $this->request->input('bulan');
                $parts = explode('-', $bulan);
                if (count($parts) === 2) {
                    $query->whereYear('tanggal', $parts[0])
                          ->whereMonth('tanggal', $parts[1]);
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

                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }
        } else {
            // Fallback for regular laporan
            if ($this->request->filled('search')) {
                $search = $this->request->input('search');
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            if ($this->request->filled('status') && $this->request->input('status') !== 'semua') {
                $query->where('status', $this->request->input('status'));
            }

            if ($this->request->filled('start_date')) {
                $query->whereDate('tanggal', '>=', $this->request->input('start_date'));
            }

            if ($this->request->filled('end_date')) {
                $query->whereDate('tanggal', '<=', $this->request->input('end_date'));
            }
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Guru',
            'Waktu Absen',
            'Status',
            'Keterangan'
        ];
    }

    public function map($row): array
    {
        return [
            $row->tanggal ?? '-',
            $row->user->name ?? '-',
            $row->waktu_absen ?? '-',
            $row->status ?? '-',
            $row->keterangan ?? ($row->foto_bukti ? 'Absen Mandiri (Foto)' : '-')
        ];
    }
}
