<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;

class LaporanController extends Controller
{
    public function piket(Request $request)
    {
        $dates = $this->getPeriodeDates($request);
        $query = RekapKehadiran::whereNotNull('jadwal_piket_id')
            ->with([
                'jadwalPiket.guruPiket',
                'jadwalPiket.titik',
                'jadwalMapel.guru',
                'jadwalMapel.mataPelajaran',
                'jadwalMapel.kelas.jurusan',
                'jadwalMapel.jamPelajarans'
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
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

        if ($dates) {
            $query->whereHas('jadwalPiket', function($q) use ($dates) {
                $q->whereDate('tanggal_piket', '>=', $dates[0])
                  ->whereDate('tanggal_piket', '<=', $dates[1]);
            });
        }

        $laporan = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('laporan/Piket', [
            'laporan' => $laporan,
            'filters' => $this->getFilters($request),
            'available_years' => $this->getAvailableYears()
        ]);
    }

    public function gms(Request $request)
    {
        $dates = $this->getPeriodeDates($request);
        $query = AbsensiGms::with(['user', 'confirmedBy', 'confirmedByUser']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'semua') {
            $query->where('status', $request->input('status'));
        }

        if ($dates) {
            $query->whereDate('tanggal', '>=', $dates[0])
                  ->whereDate('tanggal', '<=', $dates[1]);
        }

        $laporan = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('laporan/Gms', [
            'laporan' => $laporan,
            'filters' => $this->getFilters($request),
            'available_years' => $this->getAvailableYears()
        ]);
    }

    public function evaluasiKedisiplinan(Request $request)
    {
        $evaluasiCollection = $this->getEvaluasiData($request);

        $perPage = 10;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $evaluasiCollection->forPage($page, $perPage)->values(),
            $evaluasiCollection->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return Inertia::render('laporan/EvaluasiKedisiplinan', [
            'evaluasi' => $paginated,
            'filters' => array_merge($this->getFilters($request), ['show_all' => $request->input('show_all')]),
            'available_years' => $this->getAvailableYears()
        ]);
    }

    public function laporanTindakLanjutKepsek(Request $request)
    {
        $evaluasiCollection = $this->getEvaluasiData($request);

        $perPage = 10;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $evaluasiCollection->forPage($page, $perPage)->values(),
            $evaluasiCollection->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return Inertia::render('kepsek/LaporanTindakLanjut', [
            'laporan' => $paginated,
            'filters' => array_merge($this->getFilters($request), ['show_all' => $request->input('show_all')]),
            'available_years' => $this->getAvailableYears()
        ]);
    }

    public function exportLaporanTindakLanjutPdf(Request $request)
    {
        $laporan = $this->getEvaluasiData($request);
        $filterData = $this->getFilters($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.tindak_lanjut_pdf', compact('laporan', 'filterData'));
        return $pdf->download('Laporan_Tindak_Lanjut_Kedisiplinan.pdf');
    }

    public function exportLaporanTindakLanjutExcel(Request $request)
    {
        $laporan = $this->getEvaluasiData($request);
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanTindakLanjutExport($laporan), 'Laporan_Tindak_Lanjut_Kedisiplinan.xlsx');
    }

    private function getEvaluasiData(Request $request)
    {
        $dates = $this->getPeriodeDates($request);
        $startDate = $dates ? $dates[0] : null;
        $endDate = $dates ? $dates[1] : null;

        $query = \App\Models\User::whereHas('tipeGuru', function($q) {
            $q->where('nama_tipe', '!=', 'Wakasek');
        })->with('tipeGuru');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teachers = $query->get();

        $evaluasi = [];
        $showAll = $request->input('show_all') === 'true';

        foreach ($teachers as $teacher) {
            $gmsQuery = \App\Models\AbsensiGms::where('user_id', $teacher->id)
                ->where('status', 'Alpha');
            
            $piketQuery = \App\Models\RekapKehadiran::where('status_kehadiran', 'Alpha')
                ->whereHas('jadwalMapel', function($q) use ($teacher) {
                    $q->where('guru_id', $teacher->id);
                });

            $spQuery = \App\Models\TindakLanjutEvaluasi::where('user_id', $teacher->id);

            if ($startDate && $endDate) {
                $gmsQuery->whereBetween('tanggal', [$startDate, $endDate]);
                $piketQuery->whereHas('jadwalPiket', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal_piket', [$startDate, $endDate]);
                });
            }

            $gmsRecords = $gmsQuery->get(['tanggal', 'foto_bukti']);
            $piketRecords = $piketQuery->with([
                'jadwalPiket.guruPiket:id,name',
                'jadwalMapel.mataPelajaran:id,nama_mapel',
                'jadwalMapel.kelas:id,nama_kelas'
            ])->get(['id', 'jadwal_mapel_id', 'jadwal_piket_id', 'foto_dokumentasi', 'created_at']);

            $alphaDetails = collect();
            foreach ($gmsRecords as $gms) {
                $alphaDetails->push([
                    'sumber' => 'GMS',
                    'tanggal' => $gms->tanggal,
                    'foto' => $gms->foto_bukti,
                    'keterangan' => 'Alpha pada absensi GMS',
                ]);
            }
            foreach ($piketRecords as $piket) {
                $kelasName = $piket->jadwalMapel?->kelas?->nama_kelas ?? '-';
                $mapelName = $piket->jadwalMapel?->mataPelajaran?->nama_mapel ?? '-';
                $petugas = $piket->jadwalPiket?->guruPiket?->name ?? 'Petugas Piket';

                $alphaDetails->push([
                    'sumber' => 'Piket',
                    'tanggal' => $piket->jadwalPiket->tanggal_piket ?? $piket->created_at->format('Y-m-d'),
                    'foto' => $piket->foto_dokumentasi,
                    'kelas' => $kelasName,
                    'mapel' => $mapelName,
                    'petugas_piket' => $petugas,
                    'keterangan' => "Alpha di kelas {$kelasName} ({$mapelName}) - Dilaporkan oleh Guru Piket: {$petugas}",
                ]);
            }
            $alphaDetails = $alphaDetails->sortByDesc('tanggal')->values()->all();

            $totalAlpha = $gmsRecords->count() + $piketRecords->count();

            if ($totalAlpha > 0 || $showAll) {
                $spHistory = $spQuery->get(['id', 'tipe_peringatan', 'file_dokumen', 'created_at'])->toArray();

                $evaluasi[] = [
                    'id' => $teacher->id,
                    'nama' => $teacher->name,
                    'total_alpha' => $totalAlpha,
                    'alpha_details' => $alphaDetails,
                    'sp_history' => $spHistory,
                    'tipe_guru' => $teacher->tipeGuru->nama_tipe ?? '-'
                ];
            }
        }

        return collect($evaluasi)->sortByDesc('total_alpha')->values();
    }

    public function RekapitulasiKepsek(Request $request)
    {
        $mode = $request->query('mode', 'gms');
        $dates = $this->getPeriodeDates($request);
        
        $laporan = null;

        if ($mode === 'gms') {
            $query = AbsensiGms::with('user');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            if ($dates) {
                $query->whereDate('tanggal', '>=', $dates[0])
                      ->whereDate('tanggal', '<=', $dates[1]);
            }

            $laporan = $query->latest()->paginate(10)->withQueryString();
        } else {
            $query = RekapKehadiran::whereNotNull('jadwal_piket_id')
                ->with([
                    'jadwalPiket.guruPiket',
                    'jadwalPiket.titik',
                    'jadwalMapel.guru',
                    'jadwalMapel.mataPelajaran',
                    'jadwalMapel.kelas.jurusan',
                    'jadwalMapel.jamPelajarans'
                ]);

            if ($request->filled('search')) {
                $search = $request->input('search');
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

            if ($dates) {
                $query->whereHas('jadwalPiket', function($q) use ($dates) {
                    $q->whereDate('tanggal_piket', '>=', $dates[0])
                      ->whereDate('tanggal_piket', '<=', $dates[1]);
                });
            }

            $laporan = $query->latest()->paginate(10)->withQueryString();
        }

        return Inertia::render('laporan/RekapitulasiLaporanKepsek', [
            'mode' => $mode,
            'laporan' => $laporan,
            'filters' => $this->getFilters($request),
            'available_years' => $this->getAvailableYears()
        ]);
    }

    public function exportPiketPdf(Request $request)
    {
        $dates = $this->getPeriodeDates($request);
        $query = RekapKehadiran::whereNotNull('jadwal_piket_id')
            ->with(['jadwalPiket.guruPiket', 'jadwalPiket.titik', 'jadwalMapel.guru', 'jadwalMapel.mataPelajaran', 'jadwalMapel.kelas.jurusan', 'jadwalMapel.jamPelajarans']);

        if ($request->filled('search')) {
            $search = $request->input('search');
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

        if ($dates) {
            $query->whereHas('jadwalPiket', function($q) use ($dates) {
                $q->whereDate('tanggal_piket', '>=', $dates[0])
                  ->whereDate('tanggal_piket', '<=', $dates[1]);
            });
        }

        $laporan = $query->latest()->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.piket_pdf', compact('laporan'));
        return $pdf->download('Laporan_Piket.pdf');
    }

    public function exportPiketExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanPiketExport($request), 'Laporan_Piket.xlsx');
    }

    public function exportGmsPdf(Request $request)
    {
        $dates = $this->getPeriodeDates($request);
        $query = AbsensiGms::with(['user', 'confirmedBy', 'confirmedByUser']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'semua') {
            $query->where('status', $request->input('status'));
        }

        if ($dates) {
            $query->whereDate('tanggal', '>=', $dates[0])
                  ->whereDate('tanggal', '<=', $dates[1]);
        }

        $laporan = $query->latest()->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.gms_pdf', compact('laporan'));
        return $pdf->download('Laporan_GMS.pdf');
    }

    public function exportGmsExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanGmsExport($request), 'Laporan_GMS.xlsx');
    }

    public function exportRekapitulasiKepsek(Request $request)
    {
        $request->validate([
            'tipe' => 'required|in:gms,piket',
            'periode' => 'required|in:bulanan,semester',
        ]);

        if ($request->tipe === 'gms') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanGmsExport($request), 'Rekapitulasi_GMS.xlsx');
        } else {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LaporanPiketExport($request), 'Rekapitulasi_Piket.xlsx');
        }
    }

    private function getAvailableYears()
    {
        $currentYear = (int) \Carbon\Carbon::now()->year;

        $minGmsDate = \App\Models\AbsensiGms::min('tanggal');
        $minPiketDate = \App\Models\JadwalPiket::min('tanggal_piket');

        $minGmsYear = $minGmsDate
            ? (int) \Carbon\Carbon::parse($minGmsDate)->year
            : $currentYear;

        $minPiketYear = $minPiketDate
            ? (int) \Carbon\Carbon::parse($minPiketDate)->year
            : $currentYear;

        $startYear = min($currentYear, $minGmsYear, $minPiketYear);

        $years = [];
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $years[] = $year;
        }

        return $years;
    }

    private function getPeriodeDates(Request $request)
    {
        $filterType = $request->input('filter_type', 'minggu');
        $now = \Carbon\Carbon::now();

        if ($filterType === 'semua') {
            return null;
        } elseif ($filterType === 'minggu') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);
            $minggu = (int) $request->input('filter_minggu', $this->calculateWeekOfMonth($now));

            $firstDayOfMonth = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfDay();
            $startOfWeek1 = $firstDayOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            
            $startOfTargetWeek = $startOfWeek1->copy()->addWeeks($minggu - 1);
            $endOfTargetWeek = $startOfTargetWeek->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

            return [
                $startOfTargetWeek,
                $endOfTargetWeek,
            ];
        } elseif ($filterType === 'bulan') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);

            return [
                \Carbon\Carbon::create($tahun, $bulan, 1)->startOfDay(),
                \Carbon\Carbon::create($tahun, $bulan, 1)->endOfMonth(),
            ];
        }

        if ($filterType === 'semester') {
            $year = (int) $request->input('filter_tahun', $now->year);
            $semester = $request->input(
                'filter_semester',
                $now->month >= 7 ? 'Ganjil' : 'Genap'
            );

            if ($semester === 'Ganjil') {
                return [
                    \Carbon\Carbon::create($year, 7, 1)->startOfDay(),
                    \Carbon\Carbon::create($year, 12, 31)->endOfDay(),
                ];
            }

            return [
                \Carbon\Carbon::create($year, 1, 1)->startOfDay(),
                \Carbon\Carbon::create($year, 6, 30)->endOfDay(),
            ];
        }

        if ($filterType === 'tahun') {
            $year = (int) $request->input('filter_tahun', $now->year);

            return [
                \Carbon\Carbon::create($year, 1, 1)->startOfDay(),
                \Carbon\Carbon::create($year, 12, 31)->endOfDay(),
            ];
        }

        if ($filterType === 'kustom') {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                return [
                    \Carbon\Carbon::parse($request->input('start_date'))->startOfDay(),
                    \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(),
                ];
            }
        }

        return null;
    }

    private function getFilters(Request $request)
    {
        $now = \Carbon\Carbon::now();
        return [
            'filter_type' => $request->input('filter_type', 'minggu'),
            'filter_tahun' => (int) $request->input('filter_tahun', $now->year),
            'filter_bulan' => (int) $request->input('filter_bulan', $now->month),
            'filter_minggu' => (int) $request->input('filter_minggu', $this->calculateWeekOfMonth($now)),
            'filter_semester' => $request->input('filter_semester', $now->month >= 7 ? 'Ganjil' : 'Genap'),
            'search' => $request->input('search', ''),
            'status' => $request->input('status', 'semua'),
            'start_date' => $request->input('start_date', ''),
            'end_date' => $request->input('end_date', ''),
        ];
    }

    private function calculateWeekOfMonth(\Carbon\Carbon $date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $startOfFirstWeek = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $startOfCurrentWeek = $date->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        
        return $startOfFirstWeek->diffInWeeks($startOfCurrentWeek) + 1;
    }
}
