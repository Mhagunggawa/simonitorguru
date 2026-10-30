<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;
use App\Models\AbsensiUpacara;
use App\Models\JadwalPiket;
use App\Models\PegawaiAdministrasi;
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
            
        // 2. Tugas Guru Piket (Jadwal piket guru bersangkutan)
        $piketDataRaw = JadwalPiket::whereMonth('tanggal_piket', $month)
            ->whereYear('tanggal_piket', $year)
            ->with([
                'titik',
                'jamPelajarans',
                'rekapKehadirans' => function($q) {
                    $q->with(['jadwalMapel.kelas', 'jadwalMapel.mataPelajaran']);
                }
            ])
            ->get();

        // 3. Absensi GMS
        $gmsDataRaw = AbsensiGms::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();
            
        // 4. Absensi Upacara
        $upacaraDataRaw = AbsensiUpacara::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();
            
        $data = $users->map(function ($user) use ($rekapKelasRaw, $piketDataRaw, $gmsDataRaw, $upacaraDataRaw) {
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

            // --- 2. Tugas Guru Piket ---
            $userPiketRecords = $piketDataRaw->filter(function($jp) use ($user) {
                return $jp->guru_piket_id == $user->id;
            });

            $piketDetails = $userPiketRecords->map(function($jp) use ($gmsDataRaw, $user) {
                $hasReported = $jp->rekapKehadirans->count() > 0;
                
                // Cari batas waktu jam pelajaran piket terakhir
                $latestJam = $jp->jamPelajarans->sortByDesc('waktu_selesai')->first();
                $isPast = false;
                if ($latestJam && $latestJam->waktu_selesai) {
                    $waktuSelesai = substr($latestJam->waktu_selesai, 0, 5);
                    if (strlen($waktuSelesai) <= 5) $waktuSelesai .= ':00';
                    $endTime = Carbon::parse($jp->tanggal_piket . ' ' . $waktuSelesai);
                    $isPast = Carbon::now()->greaterThan($endTime);
                } else {
                    $isPast = Carbon::parse($jp->tanggal_piket)->endOfDay()->isPast();
                }

                if ($jp->status === 'Izin') {
                    $status = 'Izin';
                    $keterangan = $jp->keterangan ?: 'Izin bertugas piket.';
                } elseif ($jp->status === 'Sakit') {
                    $status = 'Sakit';
                    $keterangan = $jp->keterangan ?: 'Sakit saat jadwal piket.';
                } elseif ($hasReported || $jp->status === 'Hadir') {
                    $status = 'Hadir';
                    $keterangan = "Telah mengisi laporan piket ({$jp->rekapKehadirans->count()} kelas dipantau).";
                } elseif ($isPast || $jp->status === 'Alpha') {
                    // Fallback cek jika ada catatan Izin/Sakit dari GMS
                    $gmsOnDay = $gmsDataRaw->first(function($g) use ($user, $jp) {
                        return $g->user_id == $user->id && $g->tanggal == $jp->tanggal_piket;
                    });
                    if ($gmsOnDay && in_array($gmsOnDay->status, ['Izin', 'Sakit'])) {
                        $status = $gmsOnDay->status;
                        $keterangan = "{$gmsOnDay->status} (Berdasarkan catatan GMS: " . ($gmsOnDay->keterangan ?: '-') . ")";
                    } else {
                        $status = 'Alpha';
                        $keterangan = "Tidak mengisi laporan piket pada jadwal ini (Alpha).";
                    }
                } else {
                    $status = 'Terjadwal';
                    $keterangan = "Jadwal piket mendatang / sedang berlangsung.";
                }

                $jamList = $jp->jamPelajarans->map(function($j) {
                    $mulai = substr($j->waktu_mulai, 0, 5);
                    $selesai = substr($j->waktu_selesai, 0, 5);
                    return "{$j->nama_jam} ({$mulai}-{$selesai})";
                })->join(', ');

                $firstFoto = $jp->rekapKehadirans->firstWhere('foto_dokumentasi')?->foto_dokumentasi;

                return [
                    'tanggal' => Carbon::parse($jp->tanggal_piket)->translatedFormat('l, d F Y'),
                    'titik' => $jp->titik?->nama_titik ?? '-',
                    'jam' => $jamList ?: 'Semua Jam',
                    'status' => $status,
                    'jumlah_laporan' => $jp->rekapKehadirans->count(),
                    'keterangan' => $keterangan,
                    'foto' => $firstFoto ? asset('storage/' . $firstFoto) : null,
                ];
            })->sortByDesc('tanggal')->values();

            $piketHadir = $piketDetails->where('status', 'Hadir')->count();
            $piketIzin = $piketDetails->where('status', 'Izin')->count();
            $piketSakit = $piketDetails->where('status', 'Sakit')->count();
            $piketAlpha = $piketDetails->where('status', 'Alpha')->count();
            $piketTotal = $piketHadir + $piketIzin + $piketSakit + $piketAlpha;

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
                'piket' => [
                    'hadir' => $piketHadir,
                    'izin' => $piketIzin,
                    'sakit' => $piketSakit,
                    'alpha' => $piketAlpha,
                    'total' => $piketTotal,
                    'detail' => $piketDetails
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

        // 5. Absensi Upacara Pegawai Administrasi
        $pegawais = PegawaiAdministrasi::orderBy('nama', 'asc')->get();

        $pegawaiData = $pegawais->map(function ($pegawai) use ($upacaraDataRaw) {
            $pegawaiUpacaraRecords = $upacaraDataRaw->filter(function($r) use ($pegawai) {
                return $r->pegawai_administrasi_id == $pegawai->id;
            });

            $upacaraHadir = $pegawaiUpacaraRecords->filter(fn($r) => $r->status === 'Hadir' || ($r->is_hadir && !$r->status))->count();
            $upacaraIzin = $pegawaiUpacaraRecords->filter(fn($r) => $r->status === 'Izin')->count();
            $upacaraSakit = $pegawaiUpacaraRecords->filter(fn($r) => $r->status === 'Sakit')->count();
            $upacaraAlpha = $pegawaiUpacaraRecords->filter(fn($r) => $r->status === 'Alpha' || (!$r->is_hadir && !$r->status))->count();

            $upacaraDetails = $pegawaiUpacaraRecords->map(function($r) {
                $status = $r->status ?? ($r->is_hadir ? 'Hadir' : 'Alpha');
                return [
                    'tanggal' => Carbon::parse($r->tanggal)->translatedFormat('l, d F Y'),
                    'status' => $status,
                    'keterangan' => $r->keterangan,
                ];
            })->sortByDesc('tanggal')->values();

            return [
                'id' => $pegawai->id,
                'name' => $pegawai->nama,
                'jabatan' => 'Pegawai Administrasi',
                'upacara' => [
                    'hadir' => $upacaraHadir,
                    'izin' => $upacaraIzin,
                    'sakit' => $upacaraSakit,
                    'alpha' => $upacaraAlpha,
                    'total' => $pegawaiUpacaraRecords->count(),
                    'detail' => $upacaraDetails
                ]
            ];
        });

        return Inertia::render('LaporanKinerjaPeriodik/Index', [
            'data' => $data,
            'pegawai_data' => $pegawaiData,
            'available_years' => array_values($availableYears),
            'filters' => [
                'month' => $month,
                'year' => $year
            ]
        ]);
    }
}
