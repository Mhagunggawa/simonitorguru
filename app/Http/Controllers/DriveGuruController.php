<?php

namespace App\Http\Controllers;

use App\Models\GuruDrive;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriveGuruController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['guru', 'wakasek'])
            ->with(['tipeGuru', 'observer', 'guruDrive']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'terhubung') {
                $query->whereHas('guruDrive', function ($q) {
                    $q->whereNotNull('google_drive_url')->where('google_drive_url', '!=', '');
                });
            } elseif ($status === 'belum') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('guruDrive')
                      ->orWhereHas('guruDrive', function ($sq) {
                          $sq->whereNull('google_drive_url')->orWhere('google_drive_url', '');
                      });
                });
            }
        }

        $teachers = $query->orderBy('name')->paginate(10)->withQueryString();

        $totalGuru = User::whereIn('role', ['guru', 'wakasek'])->count();
        $terhubung = GuruDrive::whereNotNull('google_drive_url')
            ->where('google_drive_url', '!=', '')
            ->count();
        $belumTerhubung = max(0, $totalGuru - $terhubung);
        $shareObserver = GuruDrive::where('share_to_observer', true)
            ->whereNotNull('google_drive_url')
            ->where('google_drive_url', '!=', '')
            ->count();

        $stats = [
            'total_guru' => $totalGuru,
            'terhubung' => $terhubung,
            'belum_terhubung' => $belumTerhubung,
            'share_observer' => $shareObserver,
        ];

        return Inertia::render('DriveGuru/Index', [
            'teachers' => $teachers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function update(Request $request, User $guru)
    {
        if (!in_array($guru->role, ['guru', 'wakasek'])) {
            abort(404, 'User bukan berstatus guru.');
        }

        $validated = $request->validate([
            'google_drive_url' => 'nullable|string|max:1000',
            'share_to_guru' => 'required|boolean',
            'share_to_observer' => 'required|boolean',
        ], [
            'google_drive_url.max' => 'URL Google Drive terlalu panjang.',
        ]);

        GuruDrive::updateOrCreate(
            ['guru_id' => $guru->id],
            [
                'google_drive_url' => $validated['google_drive_url'] ?? null,
                'share_to_guru' => $request->boolean('share_to_guru'),
                'share_to_observer' => $request->boolean('share_to_observer'),
            ]
        );

        return back()->with('success', "Pengaturan Google Drive untuk {$guru->name} berhasil disimpan.");
    }
}
