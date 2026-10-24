<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupDataRequest;
use App\Http\Requests\MassDeleteRequest;
use App\Models\Settingan;
use App\Services\BackupService;
use App\Services\DataPurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SettinganController extends Controller
{
    public function index(): Response
    {
        $settingan = Settingan::firstOrCreate([], [
            'nama_aplikasi' => 'Simonitorguru',
            'nama_instansi' => 'Sekolah',
        ]);

        return Inertia::render('pengaturan/Index', [
            'settingan' => $settingan,
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_aplikasi' => ['required', 'string', 'max:255'],
            'nama_instansi' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $settingan = Settingan::firstOrNew();
        $settingan->fill([
            'nama_aplikasi' => $validated['nama_aplikasi'],
            'nama_instansi' => $validated['nama_instansi'] ?? null,
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            if ($logoPath === false) {
                throw new RuntimeException('Logo gagal disimpan.');
            }

            Storage::disk('public')->delete($settingan->logo_aplikasi ?? '');
            $settingan->logo_aplikasi = $logoPath;
        }

        $settingan->save();

        \App\Services\PwaService::syncPwaAssets($settingan);

        return back()->with('success', 'Identitas aplikasi berhasil diperbarui.');
    }

    public function backupData(
        BackupDataRequest $request,
        BackupService $backupService,
    ): BinaryFileResponse|JsonResponse {
        try {
            $isAllMedia = $request->isAllMedia();
            $startDate = $request->startDate();
            $endDate = $request->endDate();

            $archive = $backupService->create(
                $startDate,
                $endDate,
                $isAllMedia,
            );

            Settingan::query()->first()?->update(['last_backup_date' => now()]);

            $logDesc = $isAllMedia
                ? 'Melakukan backup data penuh (Database SQL & Seluruh Foto/Dokumen)'
                : 'Melakukan backup data periode ' . $startDate?->format('d/m/Y') . ' hingga ' . $endDate?->format('d/m/Y');

            \App\Helpers\ActivityLogger::log('BACKUP', $logDesc);

            return response()
                ->download($archive->path, $archive->filename)
                ->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Backup gagal dibuat. Pastikan mysqldump dan ekstensi ZIP tersedia di server.',
            ], 500);
        }
    }

    public function massDelete(
        MassDeleteRequest $request,
        DataPurgeService $dataPurgeService,
    ): RedirectResponse {
        $dataPurgeService->purge($request->user()?->getAuthIdentifier());

        Settingan::query()->first()?->update(['last_mass_delete_date' => now()]);

        \App\Helpers\ActivityLogger::log('DELETE', 'Melakukan pembersihan data transaksi harian (Purge)');

        return back()->with('success', 'Seluruh data transaksi harian dan berkas bukti berhasil dibersihkan.');
    }
}
