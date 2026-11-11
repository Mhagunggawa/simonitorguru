<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;
use App\Models\JadwalPiket;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function getPiketCombinedRecords(Request $request)
    {
        $dates = $this->getPeriodeDates($request);

        // 1. Rekap pemantauan kelas oleh guru piket
        $query = RekapKehadiran::whereNotNull('jadwal_piket_id')
            ->with([
                'jadwalPiket.guruPiket',
                'jadwalPiket.titik',
                'jadwalPiket.jamPelajarans',
                'jamPelajaran',
                'jadwalMapel.guru',
                'jadwalMapel.mataPelajaran',
                'jadwalMapel.kelas.jurusan',
                'jadwalMapel.jamPelajarans'
            ]);

        if ($dates) {
            $query->whereHas('jadwalPiket', function($q) use ($dates) {
                $q->whereDate('tanggal_piket', '>=', $dates[0])
                  ->whereDate('tanggal_piket', '<=', $dates[1]);
            });
        }

        $allRekaps = $query->get();

        // Kelompokkan data per sesi: jadwal_piket_id dan jam_pelajaran_id
        $sessionGroups = $allRekaps->groupBy(function($r) {
            $jamId = $r->jam_pelajaran_id 
                ?? $r->jadwalMapel?->jamPelajarans?->first()?->id 
                ?? $r->jadwalPiket?->jamPelajarans?->first()?->id 
                ?? 0;
            return $r->jadwal_piket_id . '_' . $jamId;
        });

        $sessionRows = collect();
        foreach ($sessionGroups as $groupKey => $records) {
            $first = $records->first();
            $jp = $first->jadwalPiket;
            
            // Tentukan jam pelajaran untuk sesi ini
            $jamPelajaran = $first->jamPelajaran 
                ?? $first->jadwalMapel?->jamPelajarans?->first() 
                ?? $jp?->jamPelajarans?->first();

            $urutanJam = $jamPelajaran?->urutan_jam;
            $waktuMulai = $jamPelajaran?->waktu_mulai ? substr($jamPelajaran->waktu_mulai, 0, 5) : '';
            $waktuSelesai = $jamPelajaran?->waktu_selesai ? substr($jamPelajaran->waktu_selesai, 0, 5) : '';
            $jamStr = $urutanJam ? "Jam ke-{$urutanJam}" . ($waktuMulai ? " ({$waktuMulai} - {$waktuSelesai} WIB)" : '') : '-';

            $foto = $records->firstWhere('foto_dokumentasi', '!=', null)?->foto_dokumentasi;
            $isTerlambat = $records->contains('is_terlambat', true);

            $classes = $records->map(function($r) {
                return (object)[
                    'id' => $r->id,
                    'jadwal_mapel_id' => $r->jadwal_mapel_id,
                    'kelas_nama' => $r->jadwalMapel?->kelas?->nama_kelas ?? '-',
                    'jurusan_nama' => $r->jadwalMapel?->kelas?->jurusan?->nama_jurusan ?? null,
                    'mapel_nama' => $r->jadwalMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'guru_nama' => $r->jadwalMapel?->guru?->name ?? 'Belum Ditentukan',
                    'status_kehadiran' => $r->status_kehadiran,
                    'keterangan' => $r->keterangan,
                    'is_terlambat' => $r->is_terlambat,
                    'jam_str' => $r->jadwalMapel?->jamPelajarans?->pluck('urutan_jam')->join(', ') ?: '-',
                    'jadwal_mapel' => $r->jadwalMapel,
                ];
            });

            $totalCount = $classes->count();
            $hadirCount = $classes->where('status_kehadiran', 'Hadir')->count();
            $izinCount = $classes->where('status_kehadiran', 'Izin')->count();
            $sakitCount = $classes->where('status_kehadiran', 'Sakit')->count();
            $alphaCount = $classes->where('status_kehadiran', 'Alpha')->count();
            $tidakHadirCount = $totalCount - $hadirCount;

            $ringkasanParts = [];
            if ($hadirCount > 0) $ringkasanParts[] = "{$hadirCount} Hadir";
            if ($izinCount > 0) $ringkasanParts[] = "{$izinCount} Izin";
            if ($sakitCount > 0) $ringkasanParts[] = "{$sakitCount} Sakit";
            if ($alphaCount > 0) $ringkasanParts[] = "{$alphaCount} Alpha";

            $ringkasanTeks = $hadirCount === $totalCount 
                ? "Semua Hadir ({$totalCount} Kelas)" 
                : implode(', ', $ringkasanParts);

            $sessionRows->push((object)[
                'id' => 'sesi_' . $groupKey,
                'jadwal_piket_id' => $jp?->id,
                'jadwal_piket' => $jp,
                'jam_pelajaran' => $jamPelajaran,
                'jam_str' => $jamStr,
                'urutan_jam' => $urutanJam ?? 0,
                'status_kehadiran' => 'Hadir',
                'is_piket_petugas' => false,
                'is_terlambat' => $isTerlambat,
                'foto_dokumentasi' => $foto,
                'total_kelas' => $totalCount,
                'hadir_count' => $hadirCount,
                'tidak_hadir_count' => $tidakHadirCount,
                'ringkasan_teks' => $ringkasanTeks,
                'ringkasan_status' => (object)[
                    'Hadir' => $hadirCount,
                    'Izin' => $izinCount,
                    'Sakit' => $sakitCount,
                    'Alpha' => $alphaCount,
                ],
                'classes' => $classes,
                'keterangan' => null,
                'created_at' => $first->created_at ?? ($jp?->tanggal_piket ? Carbon::parse($jp->tanggal_piket) : now()),
            ]);
        }

        // 2. Jadwal Piket tanpa laporan (Izin, Sakit, atau Alpha)
        $piketQuery = JadwalPiket::with([
            'guruPiket',
            'titik',
            'jamPelajarans',
            'rekapKehadirans'
        ]);

        if ($dates) {
            $piketQuery->whereDate('tanggal_piket', '>=', $dates[0])
                       ->whereDate('tanggal_piket', '<=', $dates[1]);
        }

        $unreportedPikets = $piketQuery->get()->filter(function($jp) {
            return $jp->rekapKehadirans->count() === 0;
        });

        $piketStatusRows = collect();
        foreach ($unreportedPikets as $jp) {
            $status = $jp->getEvaluatedStatus();

            if (in_array($status, ['Izin', 'Sakit', 'Alpha'])) {
                // Auto-sync status ke DB jika Alpha dan belum tercatat
                if ($status === 'Alpha' && $jp->status !== 'Alpha') {
                    $jp->update(['status' => 'Alpha']);
                }

                $jamPelajaran = $jp->jamPelajarans->first();
                $urutanJamList = $jp->jamPelajarans->pluck('urutan_jam')->join(', ');
                $jamStr = $urutanJamList ? "Piket Jam ke-{$urutanJamList}" : '-';

                $piketStatusRows->push((object)[
                    'id' => 'jp_' . $jp->id,
                    'jadwal_piket_id' => $jp->id,
                    'jadwal_piket' => $jp,
                    'jam_pelajaran' => $jamPelajaran,
                    'jam_str' => $jamStr,
                    'urutan_jam' => $jamPelajaran?->urutan_jam ?? 0,
                    'status_kehadiran' => $status,
                    'is_piket_petugas' => true,
                    'keterangan' => $jp->keterangan ?: ($status === 'Alpha' ? 'Guru piket tidak mengisi laporan pemantauan (Alpha)' : null),
                    'is_terlambat' => false,
                    'foto_dokumentasi' => null,
                    'total_kelas' => 0,
                    'hadir_count' => 0,
                    'tidak_hadir_count' => 0,
                    'ringkasan_teks' => "Petugas Piket {$status}",
                    'ringkasan_status' => (object)[
                        'Hadir' => 0,
                        'Izin' => 0,
                        'Sakit' => 0,
                        'Alpha' => 0,
                    ],
                    'classes' => collect(),
                    'created_at' => Carbon::parse($jp->tanggal_piket),
                ]);
            }
        }

        $allRecords = $sessionRows->concat($piketStatusRows);

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $allRecords = $allRecords->filter(function($item) use ($search) {
                $jp = $item->jadwal_piket;
                $piketGuru = strtolower($jp?->guruPiket?->name ?? '');
                $titik = strtolower($jp?->titik?->nama_titik ?? '');
                $jam = strtolower($item->jam_str ?? '');
                $statusPiket = strtolower($item->status_kehadiran ?? '');

                if (str_contains($piketGuru, $search) || str_contains($titik, $search) || str_contains($jam, $search) || str_contains($statusPiket, $search)) {
                    return true;
                }

                // Cari di dalam kelas-kelas sesi
                foreach ($item->classes as $c) {
                    if (str_contains(strtolower($c->guru_nama), $search) ||
                        str_contains(strtolower($c->mapel_nama), $search) ||
                        str_contains(strtolower($c->kelas_nama), $search) ||
                        str_contains(strtolower($c->status_kehadiran), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        // Filter Status Kehadiran (Hadir, Terlambat, Izin, Sakit, Alpha)
        if ($request->filled('status') && $request->input('status') !== 'semua') {
            $statusFilter = strtolower($request->input('status'));
            $allRecords = $allRecords->filter(function($item) use ($statusFilter) {
                if ($statusFilter === 'terlambat') {
                    return !empty($item->is_terlambat);
                }

                // Cek status sesi petugas
                if (strtolower($item->status_kehadiran ?? '') === $statusFilter) {
                    return true;
                }

                // Cek apakah ada kelas di sesi ini yang memiliki status tersebut
                foreach ($item->classes as $c) {
                    if (strtolower($c->status_kehadiran ?? '') === $statusFilter) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $allRecords->sortByDesc(function($item) {
            $jp = $item->jadwal_piket;
            $date = $jp?->tanggal_piket ?? ($item->created_at ? $item->created_at->format('Y-m-d') : '1970-01-01');
            $urutan = sprintf('%02d', $item->urutan_jam ?? 0);
            $time = $item->created_at ? $item->created_at->format('H:i:s') : '00:00:00';
            return $date . ' ' . $urutan . ' ' . $time;
        })->values();
    }

    public function piket(Request $request)
    {
        $allRecords = $this->getPiketCombinedRecords($request);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $laporan = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage)->values(),
            $allRecords->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query()
            ]
        );

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
            if ($request->input('status') === 'Hadir (Terlambat)') {
                $query->where('status', 'Hadir')->where(function($q) {
                    $q->where('is_terlambat', true)
                      ->orWhere('keterangan', 'like', '%Terlambat%');
                });
            } else if ($request->input('status') === 'Hadir') {
                $query->where('status', 'Hadir')->where(function($q) {
                    $q->where('is_terlambat', false)
                      ->where(function($sq) {
                          $sq->whereNull('keterangan')
                             ->orWhere('keterangan', 'not like', '%Terlambat%');
                      });
                });
            } else {
                $query->where('status', $request->input('status'));
            }
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
            // Perhitungan Alpha HANYA berasal dari pemantauan kelas oleh guru piket
            // (guru yang bersangkutan mengajar di kelas dan dilaporkan Alpha oleh guru piket)
            $piketQuery = \App\Models\RekapKehadiran::where('status_kehadiran', 'Alpha')
                ->whereHas('jadwalMapel', function($q) use ($teacher) {
                    $q->where('guru_id', $teacher->id);
                });

            $spQuery = \App\Models\TindakLanjutEvaluasi::where('user_id', $teacher->id);

            if ($startDate && $endDate) {
                $piketQuery->whereHas('jadwalPiket', function($q) use ($startDate, $endDate) {
                    $q->whereDate('tanggal_piket', '>=', $startDate)
                      ->whereDate('tanggal_piket', '<=', $endDate);
                });
            }

            $piketRecords = $piketQuery->with([
                'jadwalPiket.guruPiket:id,name',
                'jadwalMapel.mataPelajaran:id,nama_mapel',
                'jadwalMapel.kelas:id,nama_kelas'
            ])->get(['id', 'jadwal_mapel_id', 'jadwal_piket_id', 'status_kehadiran', 'keterangan', 'foto_dokumentasi', 'created_at']);

            $alphaDetails = collect();
            foreach ($piketRecords as $piket) {
                $kelasName = $piket->jadwalMapel?->kelas?->nama_kelas ?? '-';
                $mapelName = $piket->jadwalMapel?->mataPelajaran?->nama_mapel ?? '-';
                $petugas = $piket->jadwalPiket?->guruPiket?->name ?? 'Petugas Piket';
                $ketTambahan = !empty($piket->keterangan) ? " | Catatan: {$piket->keterangan}" : '';

                $alphaDetails->push([
                    'sumber' => 'Piket Kelas',
                    'tanggal' => $piket->jadwalPiket->tanggal_piket ?? $piket->created_at->format('Y-m-d'),
                    'foto' => $piket->foto_dokumentasi,
                    'kelas' => $kelasName,
                    'mapel' => $mapelName,
                    'petugas_piket' => $petugas,
                    'keterangan' => "Alpha di kelas {$kelasName} ({$mapelName}) - Dilaporkan oleh Guru Piket: {$petugas}{$ketTambahan}",
                ]);
            }
            $alphaDetails = $alphaDetails->sortByDesc('tanggal')->values()->all();

            $totalAlpha = $piketRecords->count();

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
        Carbon::setLocale('id');
        $dates = $this->getPeriodeDates($request);
        $laporan = $this->getPiketCombinedRecords($request);
        $settingan = \App\Models\Settingan::first();
        $periodeText = $this->getPeriodeText($request, $dates);
        $filters = $this->getFilters($request);

        $totalHadir = 0;
        $totalTerlambat = 0;
        $totalIzin = 0;
        $totalSakit = 0;
        $totalAlpha = 0;

        foreach ($laporan as $sesi) {
            if ($sesi->is_piket_petugas) {
                if ($sesi->status_kehadiran === 'Izin') $totalIzin++;
                elseif ($sesi->status_kehadiran === 'Sakit') $totalSakit++;
                elseif ($sesi->status_kehadiran === 'Alpha') $totalAlpha++;
            } else {
                if ($sesi->is_terlambat) $totalTerlambat++;
                $totalHadir += $sesi->ringkasan_status->Hadir ?? 0;
                $totalIzin += $sesi->ringkasan_status->Izin ?? 0;
                $totalSakit += $sesi->ringkasan_status->Sakit ?? 0;
                $totalAlpha += $sesi->ringkasan_status->Alpha ?? 0;
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.piket_pdf', compact(
            'laporan',
            'settingan',
            'periodeText',
            'filters',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit',
            'totalAlpha'
        ))->setPaper('a4', 'landscape');

        $safePeriode = \Illuminate\Support\Str::slug($periodeText, '_');
        $filename = 'Laporan_Piket_' . ($safePeriode ?: date('Ymd_His')) . '.pdf';
        return $pdf->download($filename);
    }

    public function exportPiketExcel(Request $request)
    {
        Carbon::setLocale('id');
        $dates = $this->getPeriodeDates($request);
        $records = $this->getPiketCombinedRecords($request);
        $periodeText = $this->getPeriodeText($request, $dates);
        $filters = $this->getFilters($request);
        $settingan = \App\Models\Settingan::first();

        $safePeriode = \Illuminate\Support\Str::slug($periodeText, '_');
        $filename = 'Laporan_Piket_' . ($safePeriode ?: date('Ymd_His')) . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanPiketExport($records, $periodeText, $filters, $settingan),
            $filename
        );
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
            if ($request->input('status') === 'Hadir (Terlambat)') {
                $query->where('status', 'Hadir')->where(function($q) {
                    $q->where('is_terlambat', true)
                      ->orWhere('keterangan', 'like', '%Terlambat%');
                });
            } else if ($request->input('status') === 'Hadir') {
                $query->where('status', 'Hadir')->where(function($q) {
                    $q->where('is_terlambat', false)
                      ->where(function($sq) {
                          $sq->whereNull('keterangan')
                             ->orWhere('keterangan', 'not like', '%Terlambat%');
                      });
                });
            } else {
                $query->where('status', $request->input('status'));
            }
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

    private function getPeriodeText(Request $request, $dates)
    {
        Carbon::setLocale('id');
        $filterType = $request->input('filter_type', 'minggu');
        $now = Carbon::now();

        if ($filterType === 'semua') {
            return 'Semua Data';
        } elseif ($filterType === 'minggu') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);
            $minggu = (int) $request->input('filter_minggu', $this->calculateWeekOfMonth($now));
            $namaBulan = Carbon::create($tahun, $bulan, 1)->translatedFormat('F');
            $rangeText = $dates ? ' (' . $dates[0]->translatedFormat('d M Y') . ' s/d ' . $dates[1]->translatedFormat('d M Y') . ')' : '';
            return "Minggu ke-{$minggu}, Bulan {$namaBulan} {$tahun}{$rangeText}";
        } elseif ($filterType === 'bulan') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);
            $namaBulan = Carbon::create($tahun, $bulan, 1)->translatedFormat('F');
            return "Bulan {$namaBulan} {$tahun}";
        } elseif ($filterType === 'semester') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $semester = $request->input('filter_semester', $now->month >= 7 ? 'Ganjil' : 'Genap');
            return "Semester {$semester} Tahun {$tahun}";
        } elseif ($filterType === 'tahun') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            return "Tahun {$tahun}";
        } elseif ($filterType === 'kustom') {
            $start = $request->input('start_date');
            $end = $request->input('end_date');
            if ($start && $end) {
                return Carbon::parse($start)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($end)->translatedFormat('d M Y');
            }
            return 'Periode Kustom';
        }

        return 'Semua Data';
    }
}
