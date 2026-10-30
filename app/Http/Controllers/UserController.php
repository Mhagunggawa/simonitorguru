<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\TipeGuru;
use App\Models\JadwalMapel;
use App\Models\JadwalPiket;
use App\Models\RekapKehadiran;
use App\Models\AbsensiGms;
use App\Models\TindakLanjutEvaluasi;
use App\Helpers\ActivityLogger;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        // 1. Query Administrator (Super Admin & Admin)
        $adminQuery = User::whereIn('role', ['super admin', 'admin']);

        if ($request->filled('search')) {
            $adminQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $role !== 'semua') {
            $adminQuery->where('role', $role);
        }

        $adminUsers = $adminQuery->orderBy('role', 'desc')->orderBy('name')->get();

        // 2. Query Guru & Tenaga Pendidik (Guru, Wakasek, Kepsek)
        $guruQuery = User::with('tipeGuru')->whereNotIn('role', ['super admin', 'admin']);

        if ($request->filled('search')) {
            $guruQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $role !== 'semua') {
            $guruQuery->where('role', $role);
        }

        $guruUsers = $guruQuery->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('users/Index', [
            'adminUsers' => $adminUsers,
            'guruUsers' => $guruUsers,
            'users' => $guruUsers, // backward compatibility
            'filters' => $request->only(['search', 'role']),
            'totalAdmins' => User::whereIn('role', ['super admin', 'admin'])->count(),
            'totalGurus' => User::whereNotIn('role', ['super admin', 'admin'])->count(),
        ]);
    }

    public function create()
    {
        $tipeGurus = TipeGuru::all();
        return Inertia::render('users/Create', ['tipeGurus' => $tipeGurus]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'users' => 'required|array|min:1',
            'users.*.name' => 'required|string|max:255',
            'users.*.email' => 'required|string|email|max:255|unique:users,email',
            'users.*.password' => 'required|string|min:8|confirmed',
            'users.*.role' => ['required', Rule::in(['super admin', 'admin', 'wakasek', 'guru', 'kepsek'])],
            'users.*.tipe_guru_id' => ['nullable', Rule::exists('tipe_gurus', 'id')],
            'users.*.status_pegawai' => ['nullable', Rule::in(['pns', 'p3k', 'p3k paruh waktu', 'gtt'])],
        ], [
            'users.*.email.unique' => 'Email pada salah satu pengguna sudah terdaftar.',
            'users.*.password.confirmed' => 'Konfirmasi password tidak cocok.'
        ]);

        if (\Illuminate\Support\Facades\Auth::user()->role === 'admin') {
            foreach ($validated['users'] as $u) {
                if (in_array($u['role'], ['super admin', 'admin'])) {
                    abort(403, 'Anda tidak diizinkan membuat user dengan role ini.');
                }
            }
        }

        foreach ($validated['users'] as $u) {
            if ($u['role'] === 'super admin') {
                abort(403, 'Tidak dapat menambahkan super admin baru.');
            }
        }

        foreach ($validated['users'] as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'tipe_guru_id' => in_array($userData['role'], ['guru', 'wakasek', 'kepsek']) ? ($userData['tipe_guru_id'] ?? null) : null,
                'status_pegawai' => in_array($userData['role'], ['guru', 'wakasek', 'kepsek']) ? ($userData['status_pegawai'] ?? null) : null,
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Users created successfully.');
    }

    public function edit(User $user)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role === 'admin' && in_array($user->role, ['super admin', 'admin'])) {
            abort(403, 'Anda tidak diizinkan mengedit user ini.');
        }

        $tipeGurus = TipeGuru::all();
        return Inertia::render('users/Edit', [
            'user' => $user,
            'tipeGurus' => $tipeGurus
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['super admin', 'admin', 'wakasek', 'guru', 'kepsek'])],
            'tipe_guru_id' => ['nullable', Rule::exists('tipe_gurus', 'id')],
            'status_pegawai' => ['nullable', Rule::in(['pns', 'p3k', 'p3k paruh waktu', 'gtt'])],
        ]);

        if (\Illuminate\Support\Facades\Auth::user()->role === 'admin') {
            if (in_array($user->role, ['super admin', 'admin']) || in_array($validated['role'], ['super admin', 'admin'])) {
                abort(403, 'Anda tidak diizinkan mengubah user ini menjadi/dari role tersebut.');
            }
        } else if (\Illuminate\Support\Facades\Auth::user()->role === 'super admin') {
            if ($user->role === 'super admin' && $validated['role'] !== 'super admin') {
                abort(403, 'Role super admin tidak dapat diubah.');
            }
            if ($user->role !== 'super admin' && $validated['role'] === 'super admin') {
                abort(403, 'Tidak dapat memberikan role super admin ke user lain.');
            }
        }

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!in_array($validated['role'], ['guru', 'wakasek', 'kepsek'])) {
            $validated['tipe_guru_id'] = null;
            $validated['status_pegawai'] = null;
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if (\Illuminate\Support\Facades\Auth::user()?->role === 'admin' && in_array($user->role, ['super admin', 'admin'])) {
            abort(403, 'Anda tidak diizinkan menghapus user ini.');
        }

        if ($user->role === 'super admin') {
            abort(403, 'Super admin tidak dapat dihapus.');
        }

        $userName = $user->name;
        $userRole = $user->role;

        try {
            // Kumpulkan file-file fisik terkait untuk dibersihkan dari storage
            $jadwalMapelIds = JadwalMapel::where('guru_id', $user->id)->pluck('id');
            $rekapMapelPhotos = RekapKehadiran::whereIn('jadwal_mapel_id', $jadwalMapelIds)
                ->whereNotNull('foto_dokumentasi')
                ->pluck('foto_dokumentasi');

            $jadwalPiketIds = JadwalPiket::where('guru_piket_id', $user->id)->pluck('id');
            $rekapPiketPhotos = RekapKehadiran::whereIn('jadwal_piket_id', $jadwalPiketIds)
                ->whereNotNull('foto_dokumentasi')
                ->pluck('foto_dokumentasi');

            $gmsPhotos = AbsensiGms::where('user_id', $user->id)
                ->whereNotNull('foto_bukti')
                ->pluck('foto_bukti');

            $spDocs = TindakLanjutEvaluasi::where('user_id', $user->id)
                ->whereNotNull('file_dokumen')
                ->pluck('file_dokumen');

            // Hapus data dalam database (foreign keys dengan ON DELETE CASCADE akan membersihkan tabel terkait secara otomatis)
            DB::transaction(function () use ($user) {
                $user->delete();
            });

            // Hapus file fisik dari storage setelah transaksi DB berhasil
            foreach ($rekapMapelPhotos->concat($rekapPiketPhotos) as $photo) {
                if ($photo && Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
            foreach ($gmsPhotos as $photo) {
                if ($photo && Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
            foreach ($spDocs as $doc) {
                if ($doc && Storage::disk('public')->exists($doc)) {
                    Storage::disk('public')->delete($doc);
                }
            }

            ActivityLogger::log('DELETE', "Menghapus user {$userName} ({$userRole}) beserta seluruh riwayat terkait.");

            return redirect()->route('users.index')->with('success', "User {$userName} berhasil dihapus beserta seluruh riwayat terkait.");
        } catch (\Throwable $e) {
            return redirect()->route('users.index')->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
