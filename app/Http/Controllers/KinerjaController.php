<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Kinerja;
use App\Models\KinerjaFokus;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\RencanaKerjaDiajukanMail;
use App\Mail\Tahap2DiajukanGuruMail;
use App\Mail\Tahap2DinilaiObserverMail;
use App\Mail\Tahap3DiajukanGuruMail;
use App\Mail\Tahap3DinilaiObserverMail;

class KinerjaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;

        // Ambil guru yang ditugaskan ke observer ini (jika user adalah observer)
        $isObserver = $user->is_observer || User::where('observer_id', $user->id)->exists();
        $teachers = [];
        if ($isObserver) {
            $teachers = User::where('observer_id', $user->id)
                ->with(['kinerjas' => function($q) use ($periodeSekarang) {
                    $q->where('periode', $periodeSekarang);
                }, 'tipeGuru', 'guruDrive'])
                ->orderBy('name')
                ->get()
                ->map(function ($teacher) use ($user) {
                    $guruDrive = $teacher->guruDrive;
                    $driveUrl = null;
                    $driveAccess = false;
                    $driveStatus = 'belum_diatur'; // 'tersedia', 'akses_dikunci', 'belum_diatur'

                    if ($guruDrive && !empty($guruDrive->google_drive_url)) {
                        $isWakasekOrSuper = in_array($user->role, ['super admin', 'wakasek']) || $user->tipe_guru_id == 2;

                        if ($isWakasekOrSuper || $guruDrive->share_to_observer) {
                            $driveUrl = $guruDrive->google_drive_url;
                            $driveAccess = true;
                            $driveStatus = 'tersedia';
                        } else {
                            $driveStatus = 'akses_dikunci';
                        }
                    }

                    $teacherArray = $teacher->toArray();
                    $teacherArray['drive_info'] = [
                        'url' => $driveUrl,
                        'has_access' => $driveAccess,
                        'status' => $driveStatus,
                    ];
                    return $teacherArray;
                });
        }

        // Role 'guru' dan 'wakasek' memiliki observasi kinerja pribadi
        $isGuru = in_array($user->role, ['guru', 'wakasek']) || $user->tipe_guru_id == 2;
        $myKinerja = null;
        $myObserver = null;
        $myDrive = null;

        if ($isGuru) {
            $myKinerja = Kinerja::with(['fokuses', 'observer', 'rejecter'])
                ->where('guru_id', $user->id)
                ->where('periode', $periodeSekarang)
                ->first();

            $myObserver = $user->observer;

            $userDrive = \App\Models\GuruDrive::where('guru_id', $user->id)->first();
            if ($userDrive && !empty($userDrive->google_drive_url)) {
                $isWakasekOrSuper = in_array($user->role, ['super admin', 'wakasek']) || $user->tipe_guru_id == 2;
                if ($isWakasekOrSuper || $userDrive->share_to_guru) {
                    $myDrive = [
                        'url' => $userDrive->google_drive_url,
                    ];
                }
            }
        }

        return Inertia::render('Kinerja/Index', [
            'teachers' => $teachers,
            'myKinerja' => $myKinerja,
            'myObserver' => $myObserver,
            'myDrive' => $myDrive,
            'isObserver' => $isObserver,
            'isGuru' => $isGuru,
        ]);
    }

    public function createTahap1(User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek', 'kepsek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses ke penilaian ini.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        
        $guru->load('observer');

        $kinerja = Kinerja::with(['fokuses', 'observer', 'guru.observer', 'rejecter'])
            ->where('guru_id', $guru->id)
            ->where('periode', $periodeSekarang)
            ->first();

        $driveLink = null;
        $guruDrive = \App\Models\GuruDrive::where('guru_id', $guru->id)->first();
        if ($guruDrive && $guruDrive->google_drive_url) {
            if (in_array($user->role, ['wakasek', 'super admin']) || $user->tipe_guru_id == 2) {
                $driveLink = $guruDrive->google_drive_url;
            } elseif ($user->id === $guru->id && $guruDrive->share_to_guru) {
                $driveLink = $guruDrive->google_drive_url;
            } elseif ($isHisObserver && $guruDrive->share_to_observer) {
                $driveLink = $guruDrive->google_drive_url;
            }
        }

        return Inertia::render('Kinerja/FormTahap1', [
            'guru' => $guru,
            'kinerja' => $kinerja,
            'currentYear' => Carbon::now()->year,
            'kurikulumDriveUrl' => $driveLink,
            'userRoleInfo' => [
                'isGuru' => $user->id === $guru->id,
                'isObserver' => ($kinerja && $kinerja->observer_id === $user->id) || ($guru->observer_id === $user->id) || ($user->is_observer && $user->id !== $guru->id),
                'isAdmin' => in_array($user->role, ['super admin', 'admin', 'wakasek']),
                'currentUserId' => $user->id
            ]
        ]);
    }

    public function storeTahap1(Request $request, User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;

        $action = $request->input('action', 'simpan_semua');

        if ($action === 'simpan_rencana') {
            // Guru mengisi Rencana Hasil Kerja dan Link Video Observasi Pembelajaran sekaligus
            $validated = $request->validate([
                'video_link' => 'required|url',
                'fokuses' => 'required|array|min:1',
                'fokuses.*.nama_fokus' => 'required|string',
                'fokuses.*.perilaku_dianjurkan' => 'required|array|min:1',
                'fokuses.*.perilaku_dianjurkan.*' => 'required|string',
                'fokuses.*.perilaku_dihindari' => 'required|array|min:1',
                'fokuses.*.perilaku_dihindari.*' => 'required|string',
            ], [
                'video_link.required' => 'Tautan video observasi pembelajaran wajib dicantumkan sebelum mengajukan ke observer.',
                'video_link.url' => 'Format tautan video harus berupa URL valid (Google Drive / YouTube).',
                'fokuses.*.nama_fokus.required' => 'Nama fokus perilaku wajib diisi.',
            ]);

            $kinerja = Kinerja::updateOrCreate(
                ['guru_id' => $guru->id, 'periode' => $periodeSekarang],
                [
                    'observer_id' => $guru->observer_id ?? $user->id,
                    'video_link' => $validated['video_link'],
                    'status_tahap1' => 'menunggu_penilaian',
                    'tahap' => 1
                ]
            );

            $existingFokuses = $kinerja->fokuses()->get();
            $kinerja->fokuses()->delete();

            foreach ($validated['fokuses'] as $i => $f) {
                $count = count($f['perilaku_dianjurkan']);
                $oldPenilaians = isset($existingFokuses[$i]) ? $existingFokuses[$i]->penilaians : null;
                $penilaians = $oldPenilaians ?? array_fill(0, $count, null);
                if (is_array($penilaians) && count($penilaians) < $count) {
                    $penilaians = array_pad($penilaians, $count, null);
                }

                $kinerja->fokuses()->create([
                    'nama_fokus' => $f['nama_fokus'],
                    'perilaku_dianjurkan' => array_values($f['perilaku_dianjurkan']),
                    'perilaku_dihindari' => array_values($f['perilaku_dihindari']),
                    'penilaians' => $penilaians,
                    'catatan' => $existingFokuses[$i]->catatan ?? null,
                ]);
            }

            // Kirim Email Notifikasi ke Observer
            $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
            if ($observerUser && !empty($observerUser->email)) {
                try {
                    Mail::to($observerUser->email)->send(
                        new RencanaKerjaDiajukanMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email rencana kerja ke observer: ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Rencana hasil kerja beserta tautan video observasi berhasil disimpan dan diajukan ke observer.');
        }

        if ($action === 'simpan_penilaian') {
            // Observer mengisi identitas/jadwal pelaksanaan, penilaian per baris, catatan, & rekomendasi
            // Karena video sudah diunggah oleh guru di awal, status langsung menjadi pending (menunggu verifikasi Wakasek)
            $validated = $request->validate([
                'tempat' => 'required|string',
                'tanggal' => 'required|date',
                'waktu' => 'required|string',
                'rekomendasi' => 'required|string',
                'fokuses' => 'required|array|min:1',
                'fokuses.*.penilaians' => 'required|array|min:1',
                'fokuses.*.penilaians.*' => 'required|integer|in:1,2,3',
                'fokuses.*.catatan' => 'required|string',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $kinerja->tempat = $validated['tempat'];
            $kinerja->tanggal = $validated['tanggal'];
            $kinerja->waktu = $validated['waktu'];
            $kinerja->rekomendasi = $validated['rekomendasi'];
            $kinerja->status_tahap1 = 'pending'; // Langsung menunggu verifikasi / ACC Wakasek
            $kinerja->revisi_target = null;
            $kinerja->catatan_revisi = null;
            $kinerja->save();

            $fokuses = $kinerja->fokuses()->get();
            foreach ($validated['fokuses'] as $idx => $f) {
                $fokusModel = $fokuses[$idx] ?? null;
                if ($fokusModel) {
                    $penilaians = $f['penilaians'];
                    $valid = array_filter($penilaians, fn($v) => is_numeric($v));
                    $avg = count($valid) > 0 ? (int) round(array_sum($valid) / count($valid)) : null;

                    $fokusModel->update([
                        'penilaians' => $penilaians,
                        'penilaian' => $avg,
                        'catatan' => $f['catatan'] ?? null,
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Penilaian observasi berhasil disimpan dan langsung diajukan ke Wakasek untuk verifikasi (ACC).');
        }

        if ($action === 'simpan_revisi_guru') {
            // Guru merevisi fokus dan tautan video pembelajaran
            $validated = $request->validate([
                'video_link' => 'required|url',
                'fokuses' => 'required|array|min:1',
                'fokuses.*.nama_fokus' => 'required|string',
                'fokuses.*.perilaku_dianjurkan' => 'required|array|min:1',
                'fokuses.*.perilaku_dihindari' => 'required|array|min:1',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $existingFokuses = $kinerja->fokuses()->get();
            $kinerja->fokuses()->delete();

            foreach ($validated['fokuses'] as $i => $f) {
                $count = count($f['perilaku_dianjurkan']);
                $oldPenilaians = isset($existingFokuses[$i]) ? $existingFokuses[$i]->penilaians : null;
                $penilaians = $f['penilaians'] ?? ($oldPenilaians ?? array_fill(0, $count, null));
                if (is_array($penilaians) && count($penilaians) < $count) {
                    $penilaians = array_pad($penilaians, $count, null);
                }

                $kinerja->fokuses()->create([
                    'nama_fokus' => $f['nama_fokus'],
                    'perilaku_dianjurkan' => array_values($f['perilaku_dianjurkan']),
                    'perilaku_dihindari' => array_values($f['perilaku_dihindari']),
                    'penilaians' => $penilaians,
                    'catatan' => $f['catatan'] ?? ($existingFokuses[$i]->catatan ?? null),
                ]);
            }

            if ($kinerja->revisi_target === 'keduanya' || $kinerja->status_tahap1 === 'revisi_keduanya') {
                $kinerja->update([
                    'video_link' => $validated['video_link'],
                    'status_tahap1' => 'revisi_observer',
                    'revisi_target' => 'observer',
                ]);

                // Kirim Email Notifikasi ke Observer bahwa Guru telah selesai merevisi
                $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
                if ($observerUser && !empty($observerUser->email)) {
                    try {
                        Mail::to($observerUser->email)->send(
                            new RencanaKerjaDiajukanMail($guru, $observerUser, $periodeSekarang)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim email revisi rencana kerja ke observer: ' . $e->getMessage());
                    }
                }

                return redirect()->back()->with('success', 'Perbaikan rencana kerja dan tautan video berhasil disimpan dan diajukan ke Observer untuk dinilai kembali.');
            } else {
                $kinerja->update([
                    'video_link' => $validated['video_link'],
                    'status_tahap1' => 'pending', // Kembali ke antrean verifikasi Wakasek
                    'revisi_tahap' => null,
                    'revisi_target' => null,
                    'catatan_revisi' => null,
                ]);

                return redirect()->back()->with('success', 'Perbaikan formulir berhasil disimpan dan diajukan kembali ke Wakasek.');
            }
        }

        if ($action === 'simpan_revisi_observer') {
            // Observer merevisi identitas/jadwal pelaksanaan, penilaian per baris, catatan, & rekomendasi
            $validated = $request->validate([
                'tempat' => 'required|string',
                'tanggal' => 'required|date',
                'waktu' => 'required|string',
                'rekomendasi' => 'required|string',
                'fokuses' => 'required|array|min:1',
                'fokuses.*.penilaians' => 'required|array|min:1',
                'fokuses.*.penilaians.*' => 'required|integer|in:1,2,3',
                'fokuses.*.catatan' => 'required|string',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $kinerja->tempat = $validated['tempat'];
            $kinerja->tanggal = $validated['tanggal'];
            $kinerja->waktu = $validated['waktu'];
            $kinerja->rekomendasi = $validated['rekomendasi'];
            $kinerja->status_tahap1 = 'pending'; // Kembali ke antrean verifikasi Wakasek
            $kinerja->revisi_tahap = null;
            $kinerja->revisi_target = null;
            $kinerja->catatan_revisi = null;
            $kinerja->save();

            $fokuses = $kinerja->fokuses()->get();
            foreach ($validated['fokuses'] as $idx => $f) {
                $fokusModel = $fokuses[$idx] ?? null;
                if ($fokusModel) {
                    $penilaians = $f['penilaians'];
                    $valid = array_filter($penilaians, fn($v) => is_numeric($v));
                    $avg = count($valid) > 0 ? (int) round(array_sum($valid) / count($valid)) : null;

                    $fokusModel->update([
                        'penilaians' => $penilaians,
                        'penilaian' => $avg,
                        'catatan' => $f['catatan'] ?? null,
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Perbaikan penilaian observasi berhasil disimpan dan diajukan kembali ke Wakasek.');
        }

        // Fallback simpan_semua
        $validated = $request->validate([
            'tempat' => 'required|string',
            'tanggal' => 'required|date',
            'waktu' => 'required|string',
            'rekomendasi' => 'nullable|string',
            'video_link' => 'nullable|url',
            'fokuses' => 'array|min:1',
            'fokuses.*.nama_fokus' => 'required|string',
            'fokuses.*.perilaku_dianjurkan' => 'array',
            'fokuses.*.perilaku_dihindari' => 'array',
        ]);

        $kinerja = Kinerja::updateOrCreate(
            ['guru_id' => $guru->id, 'periode' => $periodeSekarang],
            [
                'observer_id' => $guru->observer_id ?? $user->id,
                'tempat' => $validated['tempat'],
                'tanggal' => $validated['tanggal'],
                'waktu' => $validated['waktu'],
                'rekomendasi' => $validated['rekomendasi'] ?? null,
                'video_link' => $validated['video_link'] ?? null,
                'tahap' => 1
            ]
        );

        $kinerja->fokuses()->delete();
        foreach ($validated['fokuses'] as $f) {
            $penilaians = $f['penilaians'] ?? null;
            $avg = null;
            if (is_array($penilaians)) {
                $valid = array_filter($penilaians, fn($v) => is_numeric($v));
                $avg = count($valid) > 0 ? (int) round(array_sum($valid) / count($valid)) : null;
            }
            $kinerja->fokuses()->create([
                'nama_fokus' => $f['nama_fokus'],
                'perilaku_dianjurkan' => $f['perilaku_dianjurkan'],
                'perilaku_dihindari' => $f['perilaku_dihindari'],
                'penilaians' => $penilaians,
                'penilaian' => $avg,
                'catatan' => $f['catatan'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Data observasi kinerja berhasil diperbarui.');
    }

    public function createTahap2(User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek', 'kepsek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        
        $guru->load('observer');

        $kinerja = Kinerja::with(['observer', 'guru', 'rejecter'])
            ->where('guru_id', $guru->id)
            ->where('periode', $periodeSekarang)
            ->first();

        if (!$kinerja || $kinerja->status_tahap1 !== 'approved' || in_array($kinerja->status_tahap1, ['revisi_guru', 'revisi_observer', 'revisi_keduanya']) || $kinerja->revisi_tahap === 1) {
            $msg = 'Formulir Observasi Kinerja Tahap 1 belum disetujui (ACC).';
            if ($kinerja && (in_array($kinerja->status_tahap1, ['revisi_guru', 'revisi_observer', 'revisi_keduanya']) || $kinerja->revisi_tahap === 1)) {
                $targetLabel = $kinerja->revisi_target === 'guru' ? 'Guru' : ($kinerja->revisi_target === 'observer' ? 'Observer' : 'Guru & Observer');
                $msg = "Formulir Observasi Kinerja Tahap 1 sedang dalam status REVISI ({$targetLabel}). Silakan selesaikan perbaikan Tahap 1 dan tunggu persetujuan (ACC) dari Wakasek terlebih dahulu sebelum mengakses Tahap 2.";
            }
            return redirect()->route('kinerja.index')->with('error', $msg);
        }

        $driveLink = null;
        $guruDrive = \App\Models\GuruDrive::where('guru_id', $guru->id)->first();
        if ($guruDrive && $guruDrive->google_drive_url) {
            if (in_array($user->role, ['wakasek', 'super admin']) || $user->tipe_guru_id == 2) {
                $driveLink = $guruDrive->google_drive_url;
            } elseif ($user->id === $guru->id && $guruDrive->share_to_guru) {
                $driveLink = $guruDrive->google_drive_url;
            } elseif ($isHisObserver && $guruDrive->share_to_observer) {
                $driveLink = $guruDrive->google_drive_url;
            }
        }

        return Inertia::render('Kinerja/FormTahap2', [
            'guru' => $guru,
            'kinerja' => $kinerja,
            'currentYear' => Carbon::now()->year,
            'kurikulumDriveUrl' => $driveLink,
            'userRoleInfo' => [
                'isGuru' => $user->id === $guru->id,
                'isObserver' => ($kinerja && $kinerja->observer_id === $user->id) || ($guru->observer_id === $user->id) || ($user->is_observer && $user->id !== $guru->id),
                'isAdmin' => in_array($user->role, ['super admin', 'admin', 'wakasek']),
                'currentUserId' => $user->id
            ]
        ]);
    }

    public function storeTahap2(Request $request, User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek', 'kepsek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;

        $action = $request->input('action', 'simpan_semua');

        if ($action === 'simpan_refleksi') {
            $validated = $request->validate([
                'refleksi_rgtk' => 'nullable|string',
                'refleksi_lain' => 'nullable|string',
                'tujuan_tindak_lanjut' => 'required|array|min:1',
                'upaya_tindak_lanjut' => 'required|array|min:1',
                'kapan_tindak_lanjut' => 'required|array|min:1',
                'kebutuhan_dukungan' => 'required|string',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $kinerja->update([
                'refleksi_rgtk' => $validated['refleksi_rgtk'] ?? null,
                'refleksi_lain' => $validated['refleksi_lain'] ?? null,
                'tujuan_tindak_lanjut' => $validated['tujuan_tindak_lanjut'],
                'upaya_tindak_lanjut' => $validated['upaya_tindak_lanjut'],
                'kapan_tindak_lanjut' => $validated['kapan_tindak_lanjut'],
                'kebutuhan_dukungan' => $validated['kebutuhan_dukungan'],
                'status_tahap2' => 'menunggu_penilaian',
                'tahap' => 2,
            ]);

            $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
            if ($observerUser && !empty($observerUser->email)) {
                try {
                    Mail::to($observerUser->email)->send(
                        new Tahap2DiajukanGuruMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email refleksi guru ke observer: ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Refleksi tindak lanjut berhasil disimpan dan diajukan ke observer.');
        }

        if ($action === 'simpan_penilaian_tahap2') {
            $validated = $request->validate([
                'tempat_tahap2' => 'required|string',
                'tanggal_tahap2' => 'required|date',
                'waktu_tahap2' => 'required|string',
                'pengembangan_kompetensi' => 'nullable|array',
                'tugas_tambahan' => 'nullable|array',
                'catatan_kepsek_tahap2' => 'nullable|string',
                'kesadaran_refleksi' => 'required|integer|in:1,2,3',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $kinerja->update([
                'tempat_tahap2' => $validated['tempat_tahap2'],
                'tanggal_tahap2' => $validated['tanggal_tahap2'],
                'waktu_tahap2' => $validated['waktu_tahap2'],
                'pengembangan_kompetensi' => $validated['pengembangan_kompetensi'] ?? [],
                'tugas_tambahan' => $validated['tugas_tambahan'] ?? [],
                'catatan_kepsek_tahap2' => $validated['catatan_kepsek_tahap2'] ?? null,
                'kesadaran_refleksi' => $validated['kesadaran_refleksi'],
                'status_tahap2' => 'submitted',
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
            if ($wakaseks->isEmpty()) {
                $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
            }

            $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
            foreach ($wakaseks as $wakasek) {
                try {
                    Mail::to($wakasek->email)->send(
                        new Tahap2DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email penilaian tahap 2 ke ' . $wakasek->email . ': ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Penilaian tindak lanjut berhasil disimpan dan notifikasi email telah dikirimkan ke Wakasek untuk persetujuan (ACC).');
        }

        if ($action === 'simpan_revisi_guru_tahap2') {
            $validated = $request->validate([
                'refleksi_rgtk' => 'nullable|string',
                'refleksi_lain' => 'nullable|string',
                'tujuan_tindak_lanjut' => 'required|array|min:1',
                'upaya_tindak_lanjut' => 'required|array|min:1',
                'kapan_tindak_lanjut' => 'required|array|min:1',
                'kebutuhan_dukungan' => 'required|string',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            if ($kinerja->revisi_target === 'keduanya' || $kinerja->status_tahap2 === 'revisi_keduanya') {
                $kinerja->update([
                    'refleksi_rgtk' => $validated['refleksi_rgtk'] ?? null,
                    'refleksi_lain' => $validated['refleksi_lain'] ?? null,
                    'tujuan_tindak_lanjut' => $validated['tujuan_tindak_lanjut'],
                    'upaya_tindak_lanjut' => $validated['upaya_tindak_lanjut'],
                    'kapan_tindak_lanjut' => $validated['kapan_tindak_lanjut'],
                    'kebutuhan_dukungan' => $validated['kebutuhan_dukungan'],
                    'status_tahap2' => 'revisi_observer',
                    'revisi_target' => 'observer',
                ]);

                // Kirim email notifikasi ke Observer
                $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
                if ($observerUser && !empty($observerUser->email)) {
                    try {
                        Mail::to($observerUser->email)->send(
                            new Tahap2DiajukanGuruMail($guru, $observerUser, $periodeSekarang)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim email revisi refleksi tahap 2 ke observer: ' . $e->getMessage());
                    }
                }

                return redirect()->back()->with('success', 'Perbaikan refleksi tindak lanjut berhasil disimpan dan diajukan ke Observer untuk dinilai kembali.');
            } else {
                $kinerja->update([
                    'refleksi_rgtk' => $validated['refleksi_rgtk'] ?? null,
                    'refleksi_lain' => $validated['refleksi_lain'] ?? null,
                    'tujuan_tindak_lanjut' => $validated['tujuan_tindak_lanjut'],
                    'upaya_tindak_lanjut' => $validated['upaya_tindak_lanjut'],
                    'kapan_tindak_lanjut' => $validated['kapan_tindak_lanjut'],
                    'kebutuhan_dukungan' => $validated['kebutuhan_dukungan'],
                    'status_tahap2' => 'submitted',
                    'revisi_tahap' => null,
                    'revisi_target' => null,
                    'catatan_revisi' => null,
                ]);

                $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
                if ($wakaseks->isEmpty()) {
                    $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
                }

                $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
                foreach ($wakaseks as $wakasek) {
                    try {
                        Mail::to($wakasek->email)->send(
                            new Tahap2DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim email perbaikan refleksi guru ke ' . $wakasek->email . ': ' . $e->getMessage());
                    }
                }

                return redirect()->back()->with('success', 'Perbaikan refleksi tindak lanjut berhasil disimpan dan diajukan kembali ke Wakasek.');
            }
        }

        if ($action === 'simpan_revisi_observer_tahap2') {
            $validated = $request->validate([
                'tempat_tahap2' => 'required|string',
                'tanggal_tahap2' => 'required|date',
                'waktu_tahap2' => 'required|string',
                'pengembangan_kompetensi' => 'nullable|array',
                'tugas_tambahan' => 'nullable|array',
                'catatan_kepsek_tahap2' => 'nullable|string',
                'kesadaran_refleksi' => 'required|integer|in:1,2,3',
            ]);

            $kinerja = Kinerja::where('guru_id', $guru->id)
                ->where('periode', $periodeSekarang)
                ->firstOrFail();

            $kinerja->update([
                'tempat_tahap2' => $validated['tempat_tahap2'],
                'tanggal_tahap2' => $validated['tanggal_tahap2'],
                'waktu_tahap2' => $validated['waktu_tahap2'],
                'pengembangan_kompetensi' => $validated['pengembangan_kompetensi'] ?? [],
                'tugas_tambahan' => $validated['tugas_tambahan'] ?? [],
                'catatan_kepsek_tahap2' => $validated['catatan_kepsek_tahap2'] ?? null,
                'kesadaran_refleksi' => $validated['kesadaran_refleksi'],
                'status_tahap2' => 'submitted',
                'revisi_tahap' => null,
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
            if ($wakaseks->isEmpty()) {
                $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
            }

            $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
            foreach ($wakaseks as $wakasek) {
                try {
                    Mail::to($wakasek->email)->send(
                        new Tahap2DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email perbaikan penilaian observer ke ' . $wakasek->email . ': ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Perbaikan penilaian tindak lanjut berhasil disimpan dan diajukan kembali ke Wakasek.');
        }

        // Fallback update all
        $validated = $request->validate([
            'tempat_tahap2' => 'nullable|string',
            'tanggal_tahap2' => 'nullable|date',
            'waktu_tahap2' => 'nullable|string',
            'refleksi_rgtk' => 'nullable|string',
            'refleksi_lain' => 'nullable|string',
            'tujuan_tindak_lanjut' => 'nullable|array',
            'upaya_tindak_lanjut' => 'nullable|array',
            'kapan_tindak_lanjut' => 'nullable|array',
            'kebutuhan_dukungan' => 'nullable|string',
            'pengembangan_kompetensi' => 'nullable|array',
            'tugas_tambahan' => 'nullable|array',
            'catatan_kepsek_tahap2' => 'nullable|string',
            'kesadaran_refleksi' => 'nullable|integer|in:1,2,3',
        ]);

        $kinerja = Kinerja::where('guru_id', $guru->id)
            ->where('periode', $periodeSekarang)
            ->firstOrFail();

        $kinerja->update([
            'tempat_tahap2' => $validated['tempat_tahap2'] ?? $kinerja->tempat_tahap2,
            'tanggal_tahap2' => $validated['tanggal_tahap2'] ?? $kinerja->tanggal_tahap2,
            'waktu_tahap2' => $validated['waktu_tahap2'] ?? $kinerja->waktu_tahap2,
            'refleksi_rgtk' => $validated['refleksi_rgtk'] ?? $kinerja->refleksi_rgtk,
            'refleksi_lain' => $validated['refleksi_lain'] ?? $kinerja->refleksi_lain,
            'tujuan_tindak_lanjut' => $validated['tujuan_tindak_lanjut'] ?? $kinerja->tujuan_tindak_lanjut,
            'upaya_tindak_lanjut' => $validated['upaya_tindak_lanjut'] ?? $kinerja->upaya_tindak_lanjut,
            'kapan_tindak_lanjut' => $validated['kapan_tindak_lanjut'] ?? $kinerja->kapan_tindak_lanjut,
            'kebutuhan_dukungan' => $validated['kebutuhan_dukungan'] ?? $kinerja->kebutuhan_dukungan,
            'pengembangan_kompetensi' => $validated['pengembangan_kompetensi'] ?? $kinerja->pengembangan_kompetensi,
            'tugas_tambahan' => $validated['tugas_tambahan'] ?? $kinerja->tugas_tambahan,
            'catatan_kepsek_tahap2' => $validated['catatan_kepsek_tahap2'] ?? $kinerja->catatan_kepsek_tahap2,
            'kesadaran_refleksi' => $validated['kesadaran_refleksi'] ?? $kinerja->kesadaran_refleksi,
        ]);

        return redirect()->back()->with('success', 'Data tindak lanjut observasi kinerja berhasil diperbarui.');
    }

    public function createTahap3(User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek', 'kepsek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses ke observasi kinerja guru ini.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;

        $kinerja = Kinerja::where('guru_id', $guru->id)
            ->where('periode', $periodeSekarang)
            ->with(['fokuses', 'guru', 'observer', 'approver', 'rejecter'])
            ->first();

        // Tahap 3 hanya dapat diakses jika Tahap 2 telah disetujui, atau user adalah admin
        if (!$isAdmin && (!$kinerja || $kinerja->status_tahap2 !== 'approved')) {
            return redirect()->route('kinerja.index')->with('warning', 'Formulir Tahap 3 baru dapat dibuka setelah Formulir Tahap 2 disetujui (ACC).');
        }

        // Inisialisasi status tahap 3 jika belum ada
        if ($kinerja && empty($kinerja->status_tahap3)) {
            $kinerja->update(['status_tahap3' => 'draft_guru']);
        }

        $guruDrive = \App\Models\GuruDrive::where('guru_id', $guru->id)->first();
        $driveUrl = null;
        if ($guruDrive && !empty($guruDrive->google_drive_url)) {
            $isWakasekOrSuper = in_array($user->role, ['super admin', 'wakasek']) || $user->tipe_guru_id == 2;
            if ($isWakasekOrSuper || ($isSelf && $guruDrive->share_to_guru) || ($isHisObserver && $guruDrive->share_to_observer)) {
                $driveUrl = $guruDrive->google_drive_url;
            }
        }

        return Inertia::render('Kinerja/FormTahap3', [
            'guru' => $guru,
            'kinerja' => $kinerja,
            'currentYear' => Carbon::now()->year,
            'kurikulumDriveUrl' => $driveUrl,
            'userRoleInfo' => [
                'isGuru' => $user->id === $guru->id,
                'isObserver' => ($kinerja && $kinerja->observer_id === $user->id) || ($guru->observer_id === $user->id) || ($user->is_observer && $user->id !== $guru->id),
                'isAdmin' => in_array($user->role, ['super admin', 'admin', 'wakasek']),
                'currentUserId' => $user->id
            ]
        ]);
    }

    public function storeTahap3(Request $request, User $guru)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['super admin', 'admin', 'wakasek', 'kepsek']);
        $isSelf = $user->id === $guru->id;
        $isHisObserver = $guru->observer_id === $user->id || ($user->is_observer && $user->id !== $guru->id);

        if (!$isAdmin && !$isSelf && !$isHisObserver) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        $action = $request->input('action', 'draft_guru');

        $kinerja = Kinerja::where('guru_id', $guru->id)
            ->where('periode', $periodeSekarang)
            ->firstOrFail();

        // 1. Simpan Draf oleh Guru
        if ($action === 'draft_guru') {
            $validated = $request->validate([
                'tantangan_perubahan' => 'nullable|array',
                'inspirasi_didapat' => 'nullable|array',
                'perubahan_praktik_tahap3' => 'nullable|string',
                'perubahan_praktik_kinerja_tahap3' => 'nullable|string',
                'rencana_mengatasi_tantangan' => 'nullable|array',
            ]);

            $kinerja->update([
                'tantangan_perubahan' => $validated['tantangan_perubahan'] ?? ['', '', ''],
                'inspirasi_didapat' => array_values(array_filter($validated['inspirasi_didapat'] ?? [], fn($v) => !empty(trim($v ?? '')))),
                'perubahan_praktik_tahap3' => $validated['perubahan_praktik_tahap3'] ?? null,
                'perubahan_praktik_kinerja_tahap3' => $validated['perubahan_praktik_kinerja_tahap3'] ?? null,
                'rencana_mengatasi_tantangan' => array_values(array_filter($validated['rencana_mengatasi_tantangan'] ?? [], fn($v) => !empty(trim($v ?? '')))),
                'status_tahap3' => 'draft_guru',
            ]);

            return redirect()->back()->with('success', 'Draf refleksi Tahap 3 berhasil disimpan.');
        }

        // 2. Simpan & Ajukan oleh Guru ke Observer
        if ($action === 'simpan_refleksi_guru') {
            $validated = $request->validate([
                'tantangan_perubahan' => 'required|array',
                'tantangan_perubahan.*' => 'nullable|string',
                'inspirasi_didapat' => 'required|array|min:1',
                'inspirasi_didapat.*' => 'required|string|min:3',
                'perubahan_praktik_tahap3' => 'required|string|min:5',
                'perubahan_praktik_kinerja_tahap3' => 'required|string|min:5',
                'rencana_mengatasi_tantangan' => 'required|array|min:1',
                'rencana_mengatasi_tantangan.*' => 'required|string|min:3',
            ], [
                'tantangan_perubahan.required' => '3 Tantangan yang dihadapi wajib diisi.',
                'inspirasi_didapat.required' => 'Inspirasi yang didapat wajib diisi minimal 1 kolom.',
                'inspirasi_didapat.*.required' => 'Setiap kolom inspirasi tidak boleh dibiarkan kosong.',
                'perubahan_praktik_tahap3.required' => 'Perubahan praktik guru di ruang kelas (Upaya Mempelajari) wajib diisi.',
                'perubahan_praktik_kinerja_tahap3.required' => 'Perubahan praktik guru di ruang kelas (Peningkatan Kinerja) wajib diisi.',
                'rencana_mengatasi_tantangan.required' => 'Rencana guru dalam mengatasi tantangan wajib diisi minimal 1 kolom.',
                'rencana_mengatasi_tantangan.*.required' => 'Setiap kolom rencana mengatasi tantangan tidak boleh dibiarkan kosong.',
            ]);

            // Pastikan setidaknya satu tantangan terisi
            $filteredTantangan = array_filter($validated['tantangan_perubahan'] ?? [], fn($v) => !empty(trim($v ?? '')));
            if (empty($filteredTantangan)) {
                return redirect()->back()->withErrors(['tantangan_perubahan' => 'Harap isi setidaknya satu tantangan perubahan.']);
            }

            $kinerja->update([
                'tantangan_perubahan' => $validated['tantangan_perubahan'],
                'inspirasi_didapat' => array_values($validated['inspirasi_didapat']),
                'perubahan_praktik_tahap3' => $validated['perubahan_praktik_tahap3'],
                'perubahan_praktik_kinerja_tahap3' => $validated['perubahan_praktik_kinerja_tahap3'],
                'rencana_mengatasi_tantangan' => array_values($validated['rencana_mengatasi_tantangan']),
                'status_tahap3' => 'menunggu_penilaian',
                'tahap' => max($kinerja->tahap, 3),
            ]);

            // Kirim email notifikasi ke Observer
            $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
            if ($observerUser && !empty($observerUser->email)) {
                try {
                    Mail::to($observerUser->email)->send(
                        new Tahap3DiajukanGuruMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email refleksi tahap 3 ke observer: ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Refleksi Tahap 3 berhasil disimpan dan diajukan ke Observer.');
        }

        // 3. Simpan Penilaian oleh Observer & Kirim ke Wakasek
        if ($action === 'simpan_penilaian_tahap3') {
            $validated = $request->validate([
                'tempat_tahap3' => 'required|string',
                'tanggal_tahap3' => 'required|date',
                'waktu_tahap3' => 'required|string',
                'kesadaran_refleksi_tahap3' => 'required|integer|in:1,2,3',
                'catatan_refleksi_tahap3' => 'nullable|string',
                'upaya_kompetensi_tahap3' => 'required|integer|in:1,2,3',
                'catatan_upaya_tahap3' => 'nullable|string',
                'peningkatan_kinerja_tahap3' => 'required|integer|in:1,2,3',
                'catatan_peningkatan_tahap3' => 'nullable|string',
                'catatan_observer_tahap3' => 'nullable|string',
            ], [
                'tempat_tahap3.required' => 'Tempat pelaksanaan wajib diisi oleh observer.',
                'tanggal_tahap3.required' => 'Tanggal pelaksanaan wajib diisi oleh observer.',
                'waktu_tahap3.required' => 'Waktu pelaksanaan wajib diisi oleh observer.',
                'kesadaran_refleksi_tahap3.required' => 'Penilaian kesadaran guru ketika berefleksi wajib dipilih.',
                'upaya_kompetensi_tahap3.required' => 'Penilaian upaya guru mempelajari kompetensi wajib dipilih.',
                'peningkatan_kinerja_tahap3.required' => 'Penilaian peningkatan kinerja yang ditunjukkan guru wajib dipilih.',
            ]);

            $catatanPeningkatan = $validated['catatan_peningkatan_tahap3'] ?? $validated['catatan_observer_tahap3'] ?? null;

            $kinerja->update([
                'tempat_tahap3' => $validated['tempat_tahap3'],
                'tanggal_tahap3' => $validated['tanggal_tahap3'],
                'waktu_tahap3' => $validated['waktu_tahap3'],
                'kesadaran_refleksi_tahap3' => $validated['kesadaran_refleksi_tahap3'],
                'catatan_refleksi_tahap3' => $validated['catatan_refleksi_tahap3'] ?? null,
                'upaya_kompetensi_tahap3' => $validated['upaya_kompetensi_tahap3'],
                'catatan_upaya_tahap3' => $validated['catatan_upaya_tahap3'] ?? null,
                'peningkatan_kinerja_tahap3' => $validated['peningkatan_kinerja_tahap3'],
                'catatan_peningkatan_tahap3' => $catatanPeningkatan,
                'catatan_observer_tahap3' => $catatanPeningkatan,
                'status_tahap3' => 'submitted',
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            // Kirim email notifikasi ke Wakasek (atau Admin)
            $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
            if ($wakaseks->isEmpty()) {
                $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
            }

            $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
            foreach ($wakaseks as $wakasek) {
                try {
                    Mail::to($wakasek->email)->send(
                        new Tahap3DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email penilaian tahap 3 ke ' . $wakasek->email . ': ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Penilaian Tahap 3 berhasil disimpan dan notifikasi telah dikirimkan ke Wakasek untuk persetujuan (ACC).');
        }

        // 4. Simpan Perbaikan Revisi Guru Tahap 3
        if ($action === 'simpan_revisi_guru_tahap3') {
            $validated = $request->validate([
                'tantangan_perubahan' => 'required|array',
                'tantangan_perubahan.*' => 'nullable|string',
                'inspirasi_didapat' => 'required|array|min:1',
                'inspirasi_didapat.*' => 'required|string|min:3',
                'perubahan_praktik_tahap3' => 'required|string|min:5',
                'perubahan_praktik_kinerja_tahap3' => 'required|string|min:5',
                'rencana_mengatasi_tantangan' => 'required|array|min:1',
                'rencana_mengatasi_tantangan.*' => 'required|string|min:3',
            ]);

            if ($kinerja->revisi_target === 'keduanya' || $kinerja->status_tahap3 === 'revisi_keduanya') {
                $kinerja->update([
                    'tantangan_perubahan' => $validated['tantangan_perubahan'],
                    'inspirasi_didapat' => array_values($validated['inspirasi_didapat']),
                    'perubahan_praktik_tahap3' => $validated['perubahan_praktik_tahap3'],
                    'perubahan_praktik_kinerja_tahap3' => $validated['perubahan_praktik_kinerja_tahap3'],
                    'rencana_mengatasi_tantangan' => array_values($validated['rencana_mengatasi_tantangan']),
                    'status_tahap3' => 'revisi_observer',
                    'revisi_target' => 'observer',
                ]);

                // Kirim email notifikasi ke Observer
                $observerUser = $kinerja->observer ?? $guru->observer ?? User::find($kinerja->observer_id);
                if ($observerUser && !empty($observerUser->email)) {
                    try {
                        Mail::to($observerUser->email)->send(
                            new Tahap3DiajukanGuruMail($guru, $observerUser, $periodeSekarang)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim email revisi tahap 3 ke observer: ' . $e->getMessage());
                    }
                }

                return redirect()->back()->with('success', 'Perbaikan refleksi Tahap 3 berhasil disimpan dan diajukan ke Observer untuk dinilai kembali.');
            } else {
                $kinerja->update([
                    'tantangan_perubahan' => $validated['tantangan_perubahan'],
                    'inspirasi_didapat' => array_values($validated['inspirasi_didapat']),
                    'perubahan_praktik_tahap3' => $validated['perubahan_praktik_tahap3'],
                    'perubahan_praktik_kinerja_tahap3' => $validated['perubahan_praktik_kinerja_tahap3'],
                    'rencana_mengatasi_tantangan' => array_values($validated['rencana_mengatasi_tantangan']),
                    'status_tahap3' => 'submitted',
                    'revisi_tahap' => null,
                    'revisi_target' => null,
                    'catatan_revisi' => null,
                ]);

                $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
                if ($wakaseks->isEmpty()) {
                    $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
                }

                $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
                foreach ($wakaseks as $wakasek) {
                    try {
                        Mail::to($wakasek->email)->send(
                            new Tahap3DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim email perbaikan refleksi guru ke ' . $wakasek->email . ': ' . $e->getMessage());
                    }
                }

                return redirect()->back()->with('success', 'Perbaikan refleksi Tahap 3 berhasil disimpan dan diajukan kembali ke Wakasek.');
            }
        }

        // 5. Simpan Perbaikan Revisi Observer Tahap 3
        if ($action === 'simpan_revisi_observer_tahap3') {
            $validated = $request->validate([
                'tempat_tahap3' => 'required|string',
                'tanggal_tahap3' => 'required|date',
                'waktu_tahap3' => 'required|string',
                'kesadaran_refleksi_tahap3' => 'required|integer|in:1,2,3',
                'catatan_refleksi_tahap3' => 'nullable|string',
                'upaya_kompetensi_tahap3' => 'required|integer|in:1,2,3',
                'catatan_upaya_tahap3' => 'nullable|string',
                'peningkatan_kinerja_tahap3' => 'required|integer|in:1,2,3',
                'catatan_peningkatan_tahap3' => 'nullable|string',
                'catatan_observer_tahap3' => 'nullable|string',
            ]);

            $catatanPeningkatan = $validated['catatan_peningkatan_tahap3'] ?? $validated['catatan_observer_tahap3'] ?? null;

            $kinerja->update([
                'tempat_tahap3' => $validated['tempat_tahap3'],
                'tanggal_tahap3' => $validated['tanggal_tahap3'],
                'waktu_tahap3' => $validated['waktu_tahap3'],
                'kesadaran_refleksi_tahap3' => $validated['kesadaran_refleksi_tahap3'],
                'catatan_refleksi_tahap3' => $validated['catatan_refleksi_tahap3'] ?? null,
                'upaya_kompetensi_tahap3' => $validated['upaya_kompetensi_tahap3'],
                'catatan_upaya_tahap3' => $validated['catatan_upaya_tahap3'] ?? null,
                'peningkatan_kinerja_tahap3' => $validated['peningkatan_kinerja_tahap3'],
                'catatan_peningkatan_tahap3' => $catatanPeningkatan,
                'catatan_observer_tahap3' => $catatanPeningkatan,
                'status_tahap3' => 'submitted',
                'revisi_tahap' => null,
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            $wakaseks = User::where('role', 'wakasek')->whereNotNull('email')->get();
            if ($wakaseks->isEmpty()) {
                $wakaseks = User::whereIn('role', ['super admin', 'admin'])->whereNotNull('email')->get();
            }

            $observerUser = $kinerja->observer ?? $guru->observer ?? $user;
            foreach ($wakaseks as $wakasek) {
                try {
                    Mail::to($wakasek->email)->send(
                        new Tahap3DinilaiObserverMail($guru, $observerUser, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email perbaikan penilaian observer ke ' . $wakasek->email . ': ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Perbaikan penilaian Tahap 3 berhasil disimpan dan diajukan kembali ke Wakasek.');
        }

        return redirect()->back()->with('info', 'Tidak ada perubahan yang disimpan.');
    }
}
