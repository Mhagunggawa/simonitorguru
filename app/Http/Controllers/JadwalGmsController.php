<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JadwalGms;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JadwalGmsController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['guru', 'wakasek'])->whereHas('tipeGuru', function($q) {
            $q->where('akses_gms', true);
        });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $users = $query->with('jadwalGms', 'tipeGuru')->orderBy('name')->paginate(10)->withQueryString();

        $pengaturan = \App\Models\PengaturanGms::first();

        return Inertia::render('jadwal-gms/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'pengaturan' => $pengaturan
        ]);
    }

    public function updatePengaturan(Request $request)
    {
        $validated = $request->validate([
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'jam_normal_mulai' => 'required|date_format:H:i',
            'jam_normal_selesai' => 'required|date_format:H:i|after:jam_normal_mulai',
            'jam_terlambat_mulai' => 'required|date_format:H:i',
            'jam_terlambat_selesai' => 'required|date_format:H:i|after:jam_terlambat_mulai',
        ], [
            'waktu_mulai.required' => 'Jam mulai sesi wajib diisi.',
            'waktu_selesai.required' => 'Jam tutup sesi wajib diisi.',
            'waktu_selesai.after' => 'Jam tutup sesi harus lebih besar dari jam mulai sesi.',
            'jam_normal_mulai.required' => 'Jam mulai datang normal wajib diisi.',
            'jam_normal_selesai.required' => 'Jam selesai datang normal wajib diisi.',
            'jam_normal_selesai.after' => 'Jam selesai datang normal harus lebih besar dari jam mulai datang normal.',
            'jam_terlambat_mulai.required' => 'Jam mulai datang terlambat wajib diisi.',
            'jam_terlambat_selesai.required' => 'Jam selesai datang terlambat wajib diisi.',
            'jam_terlambat_selesai.after' => 'Jam selesai datang terlambat harus lebih besar dari jam mulai datang terlambat.',
        ]);

        $waktuMulai = $validated['waktu_mulai'];
        $waktuSelesai = $validated['waktu_selesai'];
        $normalMulai = $validated['jam_normal_mulai'];
        $normalSelesai = $validated['jam_normal_selesai'];
        $terlambatMulai = $validated['jam_terlambat_mulai'];
        $terlambatSelesai = $validated['jam_terlambat_selesai'];

        // Pengecekan jika jam normal atau jam terlambat melewati jam mulai dan jam tutup
        if ($normalMulai < $waktuMulai) {
            return back()->withErrors([
                'jam_normal_mulai' => "Jam mulai datang normal ({$normalMulai}) tidak boleh lebih awal dari Jam Mulai sesi ({$waktuMulai}). Jam mulai sesi adalah {$waktuMulai} WIB dan jam tutup adalah {$waktuSelesai} WIB."
            ]);
        }

        if ($normalSelesai > $waktuSelesai) {
            return back()->withErrors([
                'jam_normal_selesai' => "Jam selesai datang normal ({$normalSelesai}) tidak boleh melewati Jam Tutup sesi ({$waktuSelesai}). Jam mulai sesi adalah {$waktuMulai} WIB dan jam tutup adalah {$waktuSelesai} WIB."
            ]);
        }

        if ($terlambatMulai < $waktuMulai) {
            return back()->withErrors([
                'jam_terlambat_mulai' => "Jam mulai datang terlambat ({$terlambatMulai}) tidak boleh lebih awal dari Jam Mulai sesi ({$waktuMulai}). Jam mulai sesi adalah {$waktuMulai} WIB dan jam tutup adalah {$waktuSelesai} WIB."
            ]);
        }

        if ($terlambatSelesai > $waktuSelesai) {
            return back()->withErrors([
                'jam_terlambat_selesai' => "Jam selesai datang terlambat ({$terlambatSelesai}) tidak boleh melewati Jam Tutup sesi ({$waktuSelesai}). Jam mulai sesi adalah {$waktuMulai} WIB dan jam tutup adalah {$waktuSelesai} WIB."
            ]);
        }

        if ($terlambatMulai < $normalSelesai) {
            return back()->withErrors([
                'jam_terlambat_mulai' => "Jam mulai datang terlambat ({$terlambatMulai}) tidak boleh mendahului jam selesai datang normal ({$normalSelesai})."
            ]);
        }

        $dataToSave = [
            'waktu_mulai' => $waktuMulai . ':00',
            'waktu_selesai' => $waktuSelesai . ':00',
            'jam_normal_mulai' => $normalMulai . ':00',
            'jam_normal_selesai' => $normalSelesai . ':00',
            'jam_terlambat_mulai' => $terlambatMulai . ':00',
            'jam_terlambat_selesai' => $terlambatSelesai . ':00',
        ];

        $pengaturan = \App\Models\PengaturanGms::first();
        if ($pengaturan) {
            $pengaturan->update($dataToSave);
        } else {
            \App\Models\PengaturanGms::create($dataToSave);
        }

        return redirect()->route('jadwal-gms.index')->with('success', 'Pengaturan jam absensi GMS (Normal & Terlambat) berhasil diperbarui.');
    }

    public function edit(User $user)
    {
        if (!$user->tipeGuru || !$user->tipeGuru->akses_gms) {
            abort(403, 'User ini tidak memiliki hak akses GMS.');
        }

        $user->load('jadwalGms');

        return Inertia::render('jadwal-gms/Edit', [
            'guru' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!$user->tipeGuru || !$user->tipeGuru->akses_gms) {
            abort(403, 'User ini tidak memiliki hak akses GMS.');
        }

        $validated = $request->validate([
            'hari' => 'array',
            'hari.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'
        ]);

        $hari = $request->input('hari', []);

        $user->jadwalGms()->delete();

        $schedules = array_map(function($h) use ($user) {
            return [
                'user_id' => $user->id,
                'hari' => $h,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $hari);

        JadwalGms::insert($schedules);

        return redirect()->route('jadwal-gms.index')->with('success', 'Jadwal GMS berhasil diperbarui.');
    }
}
