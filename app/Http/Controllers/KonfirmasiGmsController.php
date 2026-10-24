<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\JadwalGms;
use App\Models\AbsensiGms;
use App\Models\PengaturanGms;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class KonfirmasiGmsController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('tanggal', Carbon::today()->toDateString());
        $dateObj = Carbon::parse($selectedDate);

        $hariMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        $namaHari = $hariMap[$dateObj->format('l')] ?? 'Senin';

        // Pengaturan waktu GMS
        $pengaturan = PengaturanGms::first();
        $startTimeStr = $pengaturan ? $pengaturan->waktu_mulai : '07:15:00';
        $endTimeStr = $pengaturan ? $pengaturan->waktu_selesai : '08:00:00';

        $isToday = $dateObj->isToday();
        $currentTimeStr = Carbon::now()->format('H:i:s');
        $isSessionEnded = !$isToday || ($currentTimeStr > $endTimeStr);
        $isSessionActive = $isToday && ($currentTimeStr >= $startTimeStr && $currentTimeStr <= $endTimeStr);
        $isBeforeSession = $isToday && ($currentTimeStr < $startTimeStr);

        // Ambil jadwal GMS untuk hari ini
        $jadwalQuery = JadwalGms::with(['user.tipeGuru'])
            ->where('hari', $namaHari);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $jadwalQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $jadwalList = $jadwalQuery->get();

        // Ambil absensi GMS pada tanggal tersebut
        $absensiMap = AbsensiGms::with('confirmedBy:id,name')
            ->whereDate('tanggal', $selectedDate)
            ->get()
            ->keyBy('user_id');

        // Satukan data guru terjadwal
        $teachers = $jadwalList->map(function ($jadwal) use ($absensiMap) {
            $user = $jadwal->user;
            $absensi = $absensiMap->get($jadwal->user_id);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tipe_guru' => $user->tipeGuru?->nama_tipe ?? '-',
                'status_pegawai' => strtoupper($user->status_pegawai ?? '-'),
                'is_confirmed' => (bool)$absensi,
                'status' => $absensi ? $absensi->status : 'Belum Absen',
                'waktu_absen' => $absensi?->waktu_absen ? Carbon::parse($absensi->waktu_absen)->format('H:i') : '-',
                'foto_bukti' => $absensi?->foto_bukti,
                'keterangan' => $absensi?->keterangan,
                'confirmed_by' => $absensi?->confirmedBy?->name,
                'is_manual' => (bool)($absensi?->confirmed_by),
                'absensi_id' => $absensi?->id,
            ];
        });

        // Hitung statistik
        $totalJadwal = $teachers->count();
        $hadirMandiri = $teachers->where('status', 'Hadir')->where('is_manual', false)->count();
        $hadirManual = $teachers->where('status', 'Hadir')->where('is_manual', true)->count();
        $izin = $teachers->where('status', 'Izin')->count();
        $sakit = $teachers->where('status', 'Sakit')->count();
        $alpha = $teachers->where('status', 'Alpha')->count();
        $belumAbsen = $teachers->where('is_confirmed', false)->count();

        $stats = [
            'total_jadwal' => $totalJadwal,
            'hadir_mandiri' => $hadirMandiri,
            'hadir_manual' => $hadirManual,
            'total_hadir' => $hadirMandiri + $hadirManual,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpha' => $alpha,
            'belum_absen' => $belumAbsen,
        ];

        return Inertia::render('KonfirmasiGms/Index', [
            'teachers' => $teachers->values(),
            'stats' => $stats,
            'selectedDate' => $selectedDate,
            'namaHari' => $namaHari,
            'formattedDate' => $dateObj->translatedFormat('l, d F Y'),
            'sessionInfo' => [
                'waktu_mulai' => $startTimeStr ? Carbon::parse($startTimeStr)->format('H:i') : '07:15',
                'waktu_selesai' => $endTimeStr ? Carbon::parse($endTimeStr)->format('H:i') : '08:00',
                'is_today' => $isToday,
                'is_session_ended' => $isSessionEnded,
                'is_session_active' => $isSessionActive,
                'is_before_session' => $isBeforeSession,
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'tanggal' => $selectedDate,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:Alpha,Izin,Sakit,Hadir',
            'keterangan' => [
                Rule::requiredIf(fn() => $request->input('status') === 'Hadir'),
                'nullable',
                'string',
                'max:500'
            ],
        ], [
            'keterangan.required' => 'Jika konfirmasi status adalah Hadir, kolom keterangan wajib diisi (alasan manual).',
            'status.in' => 'Status konfirmasi harus berupa Alpha, Izin, Sakit, atau Hadir.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        AbsensiGms::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'tanggal' => $validated['tanggal'],
            ],
            [
                'status' => $validated['status'],
                'waktu_absen' => now()->format('H:i:s'),
                'keterangan' => $validated['keterangan'] ?? null,
                'confirmed_by' => auth()->id(),
            ]
        );

        return back()->with('success', "Kehadiran GMS untuk {$user->name} berhasil dikonfirmasi sebagai {$validated['status']}.");
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:Alpha,Izin,Sakit',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'user_ids.required' => 'Pilih setidaknya satu guru untuk dikonfirmasi.',
            'status.in' => 'Status konfirmasi massal harus Alpha, Izin, atau Sakit.',
        ]);

        $now = now()->format('H:i:s');
        $authId = auth()->id();

        foreach ($validated['user_ids'] as $userId) {
            AbsensiGms::updateOrCreate(
                [
                    'user_id' => $userId,
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'status' => $validated['status'],
                    'waktu_absen' => $now,
                    'keterangan' => $validated['keterangan'] ?? 'Dikonfirmasi massal oleh Admin',
                    'confirmed_by' => $authId,
                ]
            );
        }

        $count = count($validated['user_ids']);
        return back()->with('success', "Berhasil mengonfirmasi {$count} guru sebagai {$validated['status']}.");
    }
}
