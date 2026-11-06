<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Jurusan;
use App\Models\JadwalPiket;
use App\Models\JadwalGms;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;
use Illuminate\Support\Facades\DB;

use App\Models\GuruDrive;
use App\Models\Kinerja;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Google Drive Pribadi (Hanya jika Wakasek memberikan izin share_to_guru atau user adalah wakasek/super admin)
        $userDrive = GuruDrive::where('guru_id', $user->id)->first();
        $myDrive = null;
        if ($userDrive && !empty($userDrive->google_drive_url)) {
            $isWakasekOrSuper = in_array($user->role, ['super admin', 'wakasek']) || $user->tipe_guru_id == 2;
            if ($userDrive->share_to_guru || $isWakasekOrSuper) {
                $myDrive = [
                    'id' => $userDrive->id,
                    'google_drive_url' => $userDrive->google_drive_url,
                    'keterangan' => $userDrive->keterangan ?? null,
                ];
            }
        }

        // 2. Google Drive Binaan (Jika observer dan diizinkan share_to_observer)
        $observedDrives = [];
        $isObserver = $user->is_observer || User::where('observer_id', $user->id)->exists();
        if ($isObserver) {
            $binaans = User::where('observer_id', $user->id)->with('guruDrive')->get();
            foreach ($binaans as $binaan) {
                $bDrive = $binaan->guruDrive;
                if ($bDrive && !empty($bDrive->google_drive_url)) {
                    $isWakasekOrSuper = in_array($user->role, ['super admin', 'wakasek']) || $user->tipe_guru_id == 2;
                    if ($bDrive->share_to_observer || $isWakasekOrSuper) {
                        $observedDrives[] = [
                            'id' => $bDrive->id,
                            'google_drive_url' => $bDrive->google_drive_url,
                            'keterangan' => $bDrive->keterangan ?? null,
                            'guru' => [
                                'id' => $binaan->id,
                                'name' => $binaan->name,
                                'email' => $binaan->email,
                            ],
                        ];
                    }
                }
            }
        }

        // 3. Status & Informasi Jadwal Guru (Piket & GMS)
        $hasJadwalPiketHariIni = JadwalPiket::where('guru_piket_id', $user->id)
            ->whereDate('tanggal_piket', today())
            ->exists();

        $hariIndo = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ][Carbon::now()->format('l')];

        $hasJadwalGmsHariIni = $user->jadwalGms()->where('hari', $hariIndo)->exists();

        // Cari hari-hari jadwal piket guru
        $piketDays = [];
        $hasAksesPiket = !$user->tipeGuru || $user->tipeGuru->akses_piket_kelas;
        if ($hasAksesPiket) {
            $now = Carbon::now();
            $semesterStart = $now->month >= 7 ? Carbon::create($now->year, 7, 1)->startOfDay() : Carbon::create($now->year, 1, 1)->startOfDay();
            $semesterEnd = $now->month >= 7 ? Carbon::create($now->year, 12, 31)->endOfDay() : Carbon::create($now->year, 6, 30)->endOfDay();

            // 1. Prioritas jadwal piket yang akan datang / hari ini
            $piketDates = JadwalPiket::where('guru_piket_id', $user->id)
                ->whereDate('tanggal_piket', '>=', today())
                ->orderBy('tanggal_piket', 'asc')
                ->pluck('tanggal_piket');

            // 2. Jika tidak ada jadwal mendatang, cek dalam semester aktif
            if ($piketDates->isEmpty()) {
                $piketDates = JadwalPiket::where('guru_piket_id', $user->id)
                    ->whereBetween('tanggal_piket', [$semesterStart->toDateString(), $semesterEnd->toDateString()])
                    ->orderBy('tanggal_piket', 'asc')
                    ->pluck('tanggal_piket');
            }

            // 3. Jika tetap belum ada, ambil dari riwayat jadwal piket yang tercatat untuk guru ini
            if ($piketDates->isEmpty()) {
                $piketDates = JadwalPiket::where('guru_piket_id', $user->id)
                    ->orderBy('tanggal_piket', 'desc')
                    ->take(10)
                    ->pluck('tanggal_piket');
            }

            if ($piketDates->isNotEmpty()) {
                Carbon::setLocale('id');
                $piketDays = $piketDates->map(function ($date) {
                    return Carbon::parse($date)->locale('id')->isoFormat('dddd');
                })->unique()->values()->all();
            }
        }

        // Cari hari-hari jadwal GMS guru
        $gmsDays = [];
        $hasAksesGms = $user->tipeGuru && $user->tipeGuru->akses_gms;
        if ($hasAksesGms) {
            $gmsDays = $user->jadwalGms()->pluck('hari')->unique()->values()->all();
        }

        $piketDaysFormatted = $this->formatDaysList($piketDays);
        $gmsDaysFormatted = $this->formatDaysList($gmsDays);
        $informasiTugas = $this->generateTugasMessage($piketDaysFormatted, $gmsDaysFormatted);

        // 4. Data counts (Admin & Super Admin)
        $counts = [
            'users' => User::count(),
            'kelas' => Kelas::count(),
            'mata_pelajaran' => MataPelajaran::count(),
            'jurusan' => Jurusan::count(),
            'jadwal_piket' => JadwalPiket::count(),
            'jadwal_gms' => JadwalGms::count(),
            'laporan_piket' => RekapKehadiran::count(),
            'laporan_gms' => AbsensiGms::count(),
        ];

        // 5. Recent Active Teachers (Guru/Wakasek)
        $activeSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->whereIn('users.role', ['guru', 'wakasek'])
            ->select('users.id', 'users.name', 'users.email', 'sessions.last_activity')
            ->orderBy('sessions.last_activity', 'desc')
            ->limit(10)
            ->get();
        $recentUsers = $activeSessions->unique('id')->take(5)->values();

        // 6. Recent Piket & GMS
        $recentPiket = RekapKehadiran::with(['jadwalPiket.guruPiket', 'jadwalPiket.titik'])
            ->latest()
            ->take(5)
            ->get();

        $recentGms = AbsensiGms::with('user')
            ->latest()
            ->take(5)
            ->get();

        // 7. Data Wakasek
        $wakasekData = [
            'gms_hari_ini' => AbsensiGms::whereDate('tanggal', today())->count(),
            'status_piket' => [
                'total' => JadwalPiket::whereDate('tanggal_piket', today())->count(),
                'masuk' => RekapKehadiran::whereDate('created_at', today())->count(),
            ],
            'pratinjau_gms' => AbsensiGms::with('user')->latest()->take(5)->get(),
            'pratinjau_piket' => RekapKehadiran::with(['jadwalPiket.guruPiket', 'jadwalPiket.titik'])->latest()->take(5)->get(),
            'notifikasi_evaluasi' => Kinerja::where(function($q) {
                $q->where('status_tahap1', 'pending')->orWhere('status_tahap2', 'submitted');
            })->count(),
        ];

        // 8. Data Kepsek
        $totalGuru = User::whereIn('role', ['guru', 'wakasek'])->count();
        $totalGmsBulanIni = AbsensiGms::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count();
        $persentaseKehadiran = $totalGuru > 0 ? min(100, round(($totalGmsBulanIni / max(1, $totalGuru * 20)) * 100)) : 100;

        $labels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->locale('id')->isoFormat('MMMM YYYY');
            $alphaCount = AbsensiGms::where('status', 'Alpha')
                ->whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->count();
            $chartData[] = $alphaCount;
        }

        $kepsekData = [
            'statistik' => [
                'total_guru' => $totalGuru,
                'persentase_kehadiran' => $persentaseKehadiran,
                'sp_bulan_ini' => 0,
            ],
            'chart' => [
                'labels' => $labels,
                'data' => $chartData,
            ],
        ];

        return Inertia::render('Dashboard', [
            'role' => $user->role,
            'user' => $user->load('tipeGuru'),
            'myDrive' => $myDrive,
            'observedDrives' => $observedDrives,
            'hasJadwalPiketHariIni' => $hasJadwalPiketHariIni,
            'hasJadwalGmsHariIni' => $hasJadwalGmsHariIni,
            'informasiTugas' => $informasiTugas,
            'piketInfo' => [
                'has_jadwal' => !empty($piketDays),
                'days' => $piketDays,
                'days_formatted' => $piketDaysFormatted,
            ],
            'gmsInfo' => [
                'has_jadwal' => !empty($gmsDays),
                'days' => $gmsDays,
                'days_formatted' => $gmsDaysFormatted,
            ],
            'counts' => $counts,
            'recentUsers' => $recentUsers,
            'recentPiket' => $recentPiket,
            'recentGms' => $recentGms,
            'wakasekData' => $wakasekData,
            'kepsekData' => $kepsekData,
        ]);
    }

    /**
     * Format daftar hari menjadi string bahasa Indonesia yang rapi (misal: "hari Rabu", "hari Senin dan Kamis")
     */
    protected function formatDaysList(array $days): string
    {
        if (empty($days)) {
            return '';
        }

        $dayOrder = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
        usort($days, function ($a, $b) use ($dayOrder) {
            return ($dayOrder[$a] ?? 99) <=> ($dayOrder[$b] ?? 99);
        });

        $days = array_values(array_unique($days));

        if (count($days) === 1) {
            return 'hari ' . $days[0];
        }

        if (count($days) === 2) {
            return 'hari ' . $days[0] . ' dan ' . $days[1];
        }

        $last = array_pop($days);
        return 'hari ' . implode(', ', $days) . ' dan ' . $last;
    }

    /**
     * Buat 1 kalimat informatif terpadu tentang status tugas Piket dan jadwal GMS
     */
    protected function generateTugasMessage(string $piketDaysFormatted, string $gmsDaysFormatted): string
    {
        $hasPiket = !empty($piketDaysFormatted);
        $hasGms = !empty($gmsDaysFormatted);

        if ($hasPiket && $hasGms) {
            return "Anda memiliki tugas piket {$piketDaysFormatted} dan jadwal GMS {$gmsDaysFormatted}.";
        }

        if ($hasPiket && !$hasGms) {
            return "Anda memiliki tugas piket {$piketDaysFormatted} dan tidak memiliki jadwal GMS.";
        }

        if (!$hasPiket && $hasGms) {
            return "Anda tidak memiliki jadwal piket dan memiliki jadwal GMS {$gmsDaysFormatted}.";
        }

        return "Anda tidak memiliki jadwal piket maupun jadwal GMS.";
    }
}
