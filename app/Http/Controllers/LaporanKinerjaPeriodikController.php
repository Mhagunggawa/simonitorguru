<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;
use App\Models\AbsensiUpacara;
use Carbon\Carbon;

class LaporanKinerjaPeriodikController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        Carbon::setLocale('id'); // ID locale for date formats

        // Get unique years from all three tables
        $yearsPiket = \App\Models\JadwalPiket::selectRaw('YEAR(tanggal_piket) as year')->distinct()->pluck('year')->toArray();
        $yearsGms = AbsensiGms::selectRaw('YEAR(tanggal) as year')->distinct()->pluck('year')->toArray();
        $yearsUpacara = AbsensiUpacara::selectRaw('YEAR(tanggal) as year')->distinct()->pluck('year')->toArray();
        
        $availableYears = array_unique(array_merge($yearsPiket, $yearsGms, $yearsUpacara, [(int)date('Y')]));
        rsort($availableYears);

        $users = User::whereIn('role', ['guru', 'wakasek'])
            ->orderBy('name', 'asc')
            ->get();

        // 1. Kehadiran di Kelas (Dipantau oleh Guru Piket)
        $rekapKelasRaw = RekapKehadiran::whereNotNull('jadwal_piket_id')
            ->whereHas('jadwalPiket', function($q) use ($month, $year) {
                $q->whereMonth('tanggal_piket', $month)
                  ->whereYear('tanggal_piket', $year);
            })
            ->with(['jadwalPiket', 'jadwalMapel.kelas', 'jadwalMapel.mataPelajaran'])
            ->get();
            
        // 2. Absensi GMS
        $gmsDataRaw = AbsensiGms::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();
            
        // 3. Absensi Upacara
        $upacaraDataRaw = AbsensiUpacara::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();
            
        $data = $users->map(function ($user) use ($rekapKelasRaw, $gmsDataRaw, $upacaraDataRaw) {
            // --- 1. Kehadiran di Kelas (Pantauan Piket) ---
            $userKelasRecords = $rekapKelasRaw->filter(function($r) use ($user) {
                return $r->jadwalMapel && $r->jadwalMapel->guru_id == $user->id;
            });

            $kelasHadir = $userKelasRecords->where('status_kehadiran', 'Hadir')->count();
            $kelasIzin = $userKelasRecords->where('status_kehadiran', 'Izin')->count();
            $kelasSakit = $userKelasRecords->where('status_kehadiran', 'Sakit')->count();
            $kelasAlpha = $userKelasRecords->whereIn('status_kehadiran', ['Alpha', 'Kosong'])->count();

            $kelasDetails = $userKelasRecords->map(function($r) {
                $tanggal = $r->jadwalPiket?->tanggal_piket ?? $r->created_at->format('Y-m-d');
                return [
                    'tanggal' => Carbon::parse($tanggal)->translatedFormat('l, d F Y'),
                    'waktu' => Carbon::parse($r->created_at)->format('H:i'),
                    'status' => $r->status_kehadiran,
                    'kelas' => $r->jadwalMapel?->kelas?->nama_kelas ?? '-',
                    'mapel' => $r->jadwalMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'keterangan' => $r->keterangan,
                    'foto' => $r->foto_dokumentasi ? asset('storage/' . $r->foto_dokumentasi) : null,
                ];
            })->sortByDesc('tanggal')->values();

            // --- 2. Absensi GMS ---
            $userGmsRecords = $gmsDataRaw->filter(function($r) use ($user) {
                return $r->user_id == $user->id;
            });

            $gmsHadir = $userGmsRecords->where('status', 'Hadir')->count();
            $gmsIzin = $userGmsRecords->where('status', 'Izin')->count();
            $gmsSakit = $userGmsRecords->where('status', 'Sakit')->count();
            $gmsAlpha = $userGmsRecords->where('status', 'Alpha')->count();

            $gmsDetails = $userGmsRecords->map(function($r) {
                return [
                    'tanggal' => Carbon::parse($r->tanggal)->translatedFormat('l, d F Y'),
                    'waktu' => $r->waktu_absen ? Carbon::parse($r->waktu_absen)->format('H:i') : '-',
                    'status' => $r->status ?? 'Hadir',
                    'keterangan' => $r->keterangan,
                    'foto' => $r->foto_bukti ? asset('storage/' . $r->foto_bukti) : null,
                ];
            })->sortByDesc('tanggal')->values();

            // --- 3. Absensi Upacara ---
            $userUpacaraRecords = $upacaraDataRaw->filter(function($r) use ($user) {
                return $r->user_id == $user->id;
            });

            $upacaraHadir = $userUpacaraRecords->filter(fn($r) => $r->status === 'Hadir' || ($r->is_hadir && !$r->status))->count();
            $upacaraIzin = $userUpacaraRecords->filter(fn($r) => $r->status === 'Izin')->count();
            $upacaraSakit = $userUpacaraRecords->filter(fn($r) => $r->status === 'Sakit')->count();
            $upacaraAlpha = $userUpacaraRecords->filter(fn($r) => $r->status === 'Alpha' || (!$r->is_hadir && !$r->status))->count();

            $upacaraDetails = $userUpacaraRecords->map(function($r) {
                $status = $r->status ?? ($r->is_hadir ? 'Hadir' : 'Alpha');
                return [
                    'tanggal' => Carbon::parse($r->tanggal)->translatedFormat('l, d F Y'),
                    'status' => $status,
                    'keterangan' => $r->keterangan,
                ];
            })->sortByDesc('tanggal')->values();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'pangkat' => strtoupper($user->status_pegawai ?? '-'),
                'kelas' => [
                    'hadir' => $kelasHadir,
                    'izin' => $kelasIzin,
                    'sakit' => $kelasSakit,
                    'alpha' => $kelasAlpha,
                    'total' => $userKelasRecords->count(),
                    'detail' => $kelasDetails
                ],
                'piket' => [ // alias for compatibility
                    'hadir' => $kelasHadir,
                    'izin' => $kelasIzin,
                    'sakit' => $kelasSakit,
                    'alpha' => $kelasAlpha,
                    'total' => $userKelasRecords->count(),
                    'detail' => $kelasDetails
                ],
                'gms' => [
                    'hadir' => $gmsHadir,
                    'izin' => $gmsIzin,
                    'sakit' => $gmsSakit,
                    'alpha' => $gmsAlpha,
                    'total' => $userGmsRecords->count(),
                    'detail' => $gmsDetails
                ],
                'upacara' => [
                    'hadir' => $upacaraHadir,
                    'izin' => $upacaraIzin,
                    'sakit' => $upacaraSakit,
                    'alpha' => $upacaraAlpha,
                    'total' => $userUpacaraRecords->count(),
                    'detail' => $upacaraDetails
                ]
            ];
        });

        return Inertia::render('LaporanKinerjaPeriodik/Index', [
            'data' => $data,
            'available_years' => array_values($availableYears),
            'filters' => [
                'month' => $month,
                'year' => $year
            ]
        ]);
    }
}
