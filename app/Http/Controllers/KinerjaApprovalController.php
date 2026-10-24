<?php

namespace App\Http\Controllers;

use App\Models\Kinerja;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\Tahap1DisetujuiMail;
use App\Mail\Tahap2DisetujuiMail;
use App\Mail\Tahap3DisetujuiMail;
use App\Mail\KinerjaRevisiMail;

class KinerjaApprovalController extends Controller
{
    public function index()
    {
        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        
        $kinerjas = Kinerja::with(['guru', 'observer', 'approver', 'rejecter'])
            ->where('periode', $periodeSekarang)
            ->where(function($q) {
                $q->where('status_tahap1', 'pending')
                  ->orWhere('status_tahap2', 'submitted')
                  ->orWhere('status_tahap3', 'submitted');
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('KinerjaApproval/Index', [
            'kinerjas' => $kinerjas
        ]);
    }

    public function show(Kinerja $kinerja)
    {
        $kinerja->load(['fokuses', 'guru', 'observer', 'approver', 'rejecter']);

        $guruDrive = \App\Models\GuruDrive::where('guru_id', $kinerja->guru_id)->first();

        if ($kinerja->revisi_tahap === 3 || ($kinerja->status_tahap2 === 'approved' && in_array($kinerja->status_tahap3, ['submitted', 'revisi_guru', 'revisi_observer', 'revisi_keduanya']))) {
            return Inertia::render('Kinerja/FormTahap3', [
                'guru' => $kinerja->guru,
                'kinerja' => $kinerja,
                'currentYear' => Carbon::now()->year,
                'kurikulumDriveUrl' => $guruDrive ? $guruDrive->google_drive_url : null,
                'isReview' => true,
                'userRoleInfo' => [
                    'isGuru' => false,
                    'isObserver' => false,
                    'isAdmin' => true,
                    'currentUserId' => auth()->id()
                ]
            ]);
        } elseif ($kinerja->revisi_tahap === 2 || ($kinerja->status_tahap1 === 'approved' && in_array($kinerja->status_tahap2, ['submitted', 'revisi_guru', 'revisi_observer', 'revisi_keduanya']))) {
            return Inertia::render('Kinerja/FormTahap2', [
                'guru' => $kinerja->guru,
                'kinerja' => $kinerja,
                'currentYear' => Carbon::now()->year,
                'kurikulumDriveUrl' => $guruDrive ? $guruDrive->google_drive_url : null,
                'isReview' => true,
                'userRoleInfo' => [
                    'isGuru' => false,
                    'isObserver' => false,
                    'isAdmin' => true,
                    'currentUserId' => auth()->id()
                ]
            ]);
        } elseif ($kinerja->revisi_tahap === 1 || in_array($kinerja->status_tahap1, ['pending', 'revisi_guru', 'revisi_observer', 'revisi_keduanya'])) {
            return Inertia::render('Kinerja/FormTahap1', [
                'guru' => $kinerja->guru,
                'kinerja' => $kinerja,
                'currentYear' => Carbon::now()->year,
                'kurikulumDriveUrl' => $guruDrive ? $guruDrive->google_drive_url : null,
                'isReview' => true,
                'userRoleInfo' => [
                    'isGuru' => false,
                    'isObserver' => false,
                    'isAdmin' => true,
                    'currentUserId' => auth()->id()
                ]
            ]);
        }
        
        return back();
    }

    public function approve(Request $request, Kinerja $kinerja)
    {
        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        $approver = $request->user();

        if ($kinerja->status_tahap1 === 'pending') {
            $kinerja->update([
                'status_tahap1' => 'approved',
                'status_tahap2' => 'draft_guru',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now(),
                'tahap' => max($kinerja->tahap, 2),
                'revisi_tahap' => null,
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            // Kirim email notifikasi ke Guru bahwa Form Tahap 1 telah disetujui
            if ($kinerja->guru && !empty($kinerja->guru->email)) {
                try {
                    Mail::to($kinerja->guru->email)->send(
                        new Tahap1DisetujuiMail($kinerja->guru, $approver, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email Tahap 1 ACC ke guru: ' . $e->getMessage());
                }
            }
        } elseif ($kinerja->status_tahap2 === 'submitted') {
            $kinerja->update([
                'status_tahap2' => 'approved',
                'status_tahap3' => 'draft_guru',
                'tahap' => max($kinerja->tahap, 3),
                'revisi_tahap' => null,
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            // Kirim email notifikasi ke Guru bahwa Form Tahap 2 telah disetujui
            if ($kinerja->guru && !empty($kinerja->guru->email)) {
                try {
                    Mail::to($kinerja->guru->email)->send(
                        new Tahap2DisetujuiMail($kinerja->guru, $approver, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email Tahap 2 ACC ke guru: ' . $e->getMessage());
                }
            }
        } elseif ($kinerja->status_tahap3 === 'submitted') {
            $kinerja->update([
                'status_tahap3' => 'approved',
                'tahap' => 4,
                'revisi_tahap' => null,
                'revisi_target' => null,
                'catatan_revisi' => null,
            ]);

            // Kirim email notifikasi ke Guru bahwa Form Tahap 3 telah disetujui / selesai penuh
            if ($kinerja->guru && !empty($kinerja->guru->email)) {
                try {
                    Mail::to($kinerja->guru->email)->send(
                        new Tahap3DisetujuiMail($kinerja->guru, $approver, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email Tahap 3 ACC ke guru: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Form Kinerja berhasil disetujui (ACC). Notifikasi email telah dikirimkan ke guru.');
    }

    public function reject(Request $request, Kinerja $kinerja)
    {
        $validated = $request->validate([
            'revisi_target' => 'required|in:guru,observer,keduanya',
            'catatan_revisi' => 'required|string|min:3',
            'tahap' => 'nullable|integer|in:1,2,3',
        ]);

        $periodeSekarang = 'Januari-Desember ' . Carbon::now()->year;
        $rejecter = $request->user();
        $target = $validated['revisi_target'];
        $catatan = $validated['catatan_revisi'];

        $kinerja->load(['guru', 'observer']);
        $guru = $kinerja->guru;
        $observer = $kinerja->observer ?? ($guru ? $guru->observer : null);

        // Tentukan tahap penolakan dari request atau fallback ke status submitted
        $tahap = (int) ($request->input('tahap') ?: ($kinerja->status_tahap3 === 'submitted' ? 3 : ($kinerja->status_tahap2 === 'submitted' ? 2 : 1)));

        $newStatus = match ($target) {
            'guru' => 'revisi_guru',
            'observer' => 'revisi_observer',
            'keduanya' => 'revisi_keduanya',
        };

        if ($tahap === 3) {
            // Status spesifik per target penolakan Tahap 3
            $kinerja->update([
                'status_tahap3' => $newStatus,
                'revisi_tahap' => 3,
                'revisi_target' => $target,
                'catatan_revisi' => $catatan,
                'rejected_by' => $rejecter->id,
                'rejected_at' => Carbon::now(),
            ]);
        } elseif ($tahap === 2) {
            // Status spesifik per target penolakan Tahap 2
            $kinerja->update([
                'status_tahap2' => $newStatus,
                'revisi_tahap' => 2,
                'revisi_target' => $target,
                'catatan_revisi' => $catatan,
                'rejected_by' => $rejecter->id,
                'rejected_at' => Carbon::now(),
            ]);
        } else {
            // Status spesifik per target penolakan Tahap 1
            $kinerja->update([
                'status_tahap1' => $newStatus,
                'status_tahap2' => 'draft_guru', // Kembalikan tahap 2 ke draft jika tahap 1 direvisi
                'tahap' => 1,
                'revisi_tahap' => 1,
                'revisi_target' => $target,
                'catatan_revisi' => $catatan,
                'rejected_by' => $rejecter->id,
                'rejected_at' => Carbon::now(),
            ]);
        }

        // Pengiriman Email Notifikasi
        if ($target === 'guru') {
            if ($guru && !empty($guru->email)) {
                try {
                    Mail::to($guru->email)->send(
                        new KinerjaRevisiMail($guru, 'guru', $guru, $observer, $tahap, $target, $catatan, $rejecter, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email revisi ke guru: ' . $e->getMessage());
                }
            }
        } elseif ($target === 'observer') {
            if ($observer && !empty($observer->email)) {
                try {
                    Mail::to($observer->email)->send(
                        new KinerjaRevisiMail($observer, 'observer', $guru, $observer, $tahap, $target, $catatan, $rejecter, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email revisi ke observer: ' . $e->getMessage());
                }
            }
        } elseif ($target === 'keduanya') {
            if ($guru && !empty($guru->email)) {
                try {
                    Mail::to($guru->email)->send(
                        new KinerjaRevisiMail($guru, 'guru', $guru, $observer, $tahap, $target, $catatan, $rejecter, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email revisi ke guru (keduanya): ' . $e->getMessage());
                }
            }
            if ($observer && !empty($observer->email)) {
                try {
                    Mail::to($observer->email)->send(
                        new KinerjaRevisiMail($observer, 'observer', $guru, $observer, $tahap, $target, $catatan, $rejecter, $periodeSekarang)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email revisi ke observer (keduanya): ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('kinerja-approval.index')->with('success', 'Form Kinerja berhasil ditolak dan permintaan revisi telah dikirimkan melalui email.');
    }
}
