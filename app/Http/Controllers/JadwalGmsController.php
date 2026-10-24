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
        ]);

        // Formatting back to H:i:s
        $waktu_mulai = $validated['waktu_mulai'] . ':00';
        $waktu_selesai = $validated['waktu_selesai'] . ':00';

        $pengaturan = \App\Models\PengaturanGms::first();
        if ($pengaturan) {
            $pengaturan->update([
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai
            ]);
        } else {
            \App\Models\PengaturanGms::create([
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai
            ]);
        }

        return redirect()->route('jadwal-gms.index')->with('success', 'Pengaturan jam absensi GMS berhasil diperbarui.');
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
