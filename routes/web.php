<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/generate-link', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Tautan penyimpanan berhasil diperbarui di server!';
});

Route::get('/manifest.json', function () {
    return response()->file(public_path('manifest.json'), [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, private',
    ]);
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(['role:guru,wakasek'])->group(function () {
        Route::get('pelaporan-piket', [\App\Http\Controllers\PelaporanPiketController::class, 'index'])->name('pelaporan-piket.index');
        Route::get('pelaporan-piket/{id}/create', [\App\Http\Controllers\PelaporanPiketController::class, 'create'])->name('pelaporan-piket.create');
        Route::post('pelaporan-piket', [\App\Http\Controllers\PelaporanPiketController::class, 'store'])->name('pelaporan-piket.store');
        Route::get('absensi-gms/create', [\App\Http\Controllers\AbsensiGmsController::class, 'create'])->name('absensi-gms.create');
        Route::post('absensi-gms', [\App\Http\Controllers\AbsensiGmsController::class, 'store'])->name('absensi-gms.store');
    });

    // Kinerja Routes for Observers (Bisa diakses oleh Guru, Wakasek, Kepsek yang berstatus observer)
    Route::get('kinerja', [\App\Http\Controllers\KinerjaController::class, 'index'])->name('kinerja.index');
    Route::get('kinerja/{guru}/tahap-1', [\App\Http\Controllers\KinerjaController::class, 'createTahap1'])->name('kinerja.tahap1');
    Route::post('kinerja/{guru}/tahap-1', [\App\Http\Controllers\KinerjaController::class, 'storeTahap1']);
    Route::get('kinerja/{guru}/tahap-2', [\App\Http\Controllers\KinerjaController::class, 'createTahap2'])->name('kinerja.tahap2');
    Route::post('kinerja/{guru}/tahap-2', [\App\Http\Controllers\KinerjaController::class, 'storeTahap2']);
    Route::get('kinerja/{guru}/tahap-3', [\App\Http\Controllers\KinerjaController::class, 'createTahap3'])->name('kinerja.tahap3');
    Route::post('kinerja/{guru}/tahap-3', [\App\Http\Controllers\KinerjaController::class, 'storeTahap3']);

    Route::middleware(['role:super admin,admin'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('mata-pelajaran', \App\Http\Controllers\MataPelajaranController::class)->parameters([
            'mata-pelajaran' => 'mataPelajaran'
        ]);
        Route::resource('jurusan', \App\Http\Controllers\JurusanController::class);
        Route::resource('kelas', \App\Http\Controllers\KelasController::class)->parameters([
            'kelas' => 'kelas'
        ]);
        Route::resource('tingkat-kelas', \App\Http\Controllers\TingkatKelasController::class)->parameters([
            'tingkat-kelas' => 'tingkatKelas'
        ]);
        
        Route::resource('titik', \App\Http\Controllers\TitikController::class);
        Route::resource('jam-pelajaran', \App\Http\Controllers\JamPelajaranController::class)->parameters([
            'jam-pelajaran' => 'jamPelajaran'
        ]);
        Route::get('jadwal-mapel/fetch', [\App\Http\Controllers\JadwalMapelController::class, 'fetch'])->name('jadwal-mapel.fetch');
        Route::resource('jadwal-mapel', \App\Http\Controllers\JadwalMapelController::class)->only(['index', 'create', 'store']);
        Route::resource('jadwal-piket', \App\Http\Controllers\JadwalPiketController::class)->parameters([
            'jadwal-piket' => 'jadwalPiket'
        ]);
        
        Route::get('jadwal-gms', [\App\Http\Controllers\JadwalGmsController::class, 'index'])->name('jadwal-gms.index');
        Route::get('jadwal-gms/{user}/edit', [\App\Http\Controllers\JadwalGmsController::class, 'edit'])->name('jadwal-gms.edit');
        Route::post('jadwal-gms/pengaturan', [\App\Http\Controllers\JadwalGmsController::class, 'updatePengaturan'])->name('jadwal-gms.pengaturan.update');
        Route::post('jadwal-gms/{user}', [\App\Http\Controllers\JadwalGmsController::class, 'update'])->name('jadwal-gms.update');
        
        Route::get('absensi-upacara', [\App\Http\Controllers\AbsensiUpacaraController::class, 'index'])->name('absensi-upacara.index');
        Route::post('absensi-upacara', [\App\Http\Controllers\AbsensiUpacaraController::class, 'store'])->name('absensi-upacara.store');
    });

    Route::middleware(['role:super admin'])->group(function () {
        Route::resource('tipe-guru', \App\Http\Controllers\TipeGuruController::class)->parameters([
            'tipe-guru' => 'tipeGuru'
        ]);

        Route::get('pengaturan', [\App\Http\Controllers\SettinganController::class, 'index'])->name('pengaturan.index');
        Route::post('pengaturan/identity', [\App\Http\Controllers\SettinganController::class, 'updateIdentity'])->name('pengaturan.identity');
        Route::post('pengaturan/backup', [\App\Http\Controllers\SettinganController::class, 'backupData'])->name('pengaturan.backup');
        Route::post('pengaturan/mass-delete', [\App\Http\Controllers\SettinganController::class, 'massDelete'])->name('pengaturan.mass-delete');
        
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    Route::middleware(['role:super admin,wakasek,kepsek'])->group(function () {
        Route::get('laporan/piket', [\App\Http\Controllers\LaporanController::class, 'piket'])->name('laporan.piket');
        Route::get('laporan/piket/export-pdf', [\App\Http\Controllers\LaporanController::class, 'exportPiketPdf'])->name('laporan.piket.export.pdf');
        Route::get('laporan/piket/export-excel', [\App\Http\Controllers\LaporanController::class, 'exportPiketExcel'])->name('laporan.piket.export.excel');
        
        // Izin Piket Routes (Wakasek & Admin)
        Route::get('izin-piket', [\App\Http\Controllers\IzinPiketController::class, 'index'])->name('izin-piket.index');
        Route::post('izin-piket/{id}/approve', [\App\Http\Controllers\IzinPiketController::class, 'approve'])->name('izin-piket.approve');
        
        Route::get('laporan/gms', [\App\Http\Controllers\LaporanController::class, 'gms'])->name('laporan.gms');
        Route::get('laporan/gms/export-pdf', [\App\Http\Controllers\LaporanController::class, 'exportGmsPdf'])->name('laporan.gms.export.pdf');
        Route::get('laporan/gms/export-excel', [\App\Http\Controllers\LaporanController::class, 'exportGmsExcel'])->name('laporan.gms.export.excel');
        
    });

    Route::middleware(['role:super admin,wakasek'])->group(function () {
        Route::get('laporan-absensi-upacara', [\App\Http\Controllers\AbsensiUpacaraReportController::class, 'index'])->name('laporan-absensi-upacara.index');
    });

    Route::middleware(['role:wakasek,kepsek'])->group(function () {
        Route::get('kurikulum/drive-guru', [\App\Http\Controllers\DriveGuruController::class, 'index'])->name('kurikulum.drive-guru.index');
        Route::post('kurikulum/drive-guru/{guru}', [\App\Http\Controllers\DriveGuruController::class, 'update'])->name('kurikulum.drive-guru.update');

        Route::get('observers', [\App\Http\Controllers\ObserverController::class, 'index'])->name('observers.index');
        Route::post('observers/{user}/toggle', [\App\Http\Controllers\ObserverController::class, 'toggleObserver'])->name('observers.toggle');
        Route::post('observers/{user}/assign', [\App\Http\Controllers\ObserverController::class, 'assignTeachers'])->name('observers.assign');

        Route::get('laporan-kinerja-periodik', [\App\Http\Controllers\LaporanKinerjaPeriodikController::class, 'index'])->name('laporan-kinerja-periodik.index');

        Route::get('laporan/kinerja-tahunan', [\App\Http\Controllers\LaporanKinerjaTahunanController::class, 'index'])->name('laporan.kinerja-tahunan');
        Route::get('laporan-kinerja-tahunan', [\App\Http\Controllers\LaporanKinerjaTahunanController::class, 'index'])->name('laporan-kinerja-tahunan.index');
    });

    Route::middleware(['role:kepsek'])->group(function () {
        Route::get('kepsek/laporan', [\App\Http\Controllers\LaporanController::class, 'RekapitulasiKepsek'])->name('kepsek.rekapitulasi');
        Route::get('kepsek/laporan/export', [\App\Http\Controllers\LaporanController::class, 'exportRekapitulasiKepsek'])->name('kepsek.rekapitulasi.export');
        
        Route::get('kepsek/laporan-tindak-lanjut', [\App\Http\Controllers\LaporanController::class, 'laporanTindakLanjutKepsek'])->name('kepsek.laporan-tindak-lanjut');
        Route::get('kepsek/laporan-tindak-lanjut/export-pdf', [\App\Http\Controllers\LaporanController::class, 'exportLaporanTindakLanjutPdf'])->name('kepsek.laporan-tindak-lanjut.pdf');
        Route::get('kepsek/laporan-tindak-lanjut/export-excel', [\App\Http\Controllers\LaporanController::class, 'exportLaporanTindakLanjutExcel'])->name('kepsek.laporan-tindak-lanjut.excel');
    });

    Route::middleware(['role:wakasek'])->group(function () {
        Route::get('laporan/evaluasi', [\App\Http\Controllers\LaporanController::class, 'evaluasiKedisiplinan'])->name('laporan.evaluasi');
        Route::post('laporan/evaluasi', [\App\Http\Controllers\TindakLanjutController::class, 'store'])->name('laporan.evaluasi.store');
        Route::delete('laporan/evaluasi/{id}', [\App\Http\Controllers\TindakLanjutController::class, 'destroy'])->name('laporan.evaluasi.destroy');

        Route::get('kinerja-approval', [\App\Http\Controllers\KinerjaApprovalController::class, 'index'])->name('kinerja-approval.index');
        Route::get('kinerja-approval/{kinerja}/show', [\App\Http\Controllers\KinerjaApprovalController::class, 'show'])->name('kinerja-approval.show');
        Route::post('kinerja-approval/{kinerja}/approve', [\App\Http\Controllers\KinerjaApprovalController::class, 'approve'])->name('kinerja-approval.approve');
        Route::post('kinerja-approval/{kinerja}/reject', [\App\Http\Controllers\KinerjaApprovalController::class, 'reject'])->name('kinerja-approval.reject');
    });

    Route::middleware(['role:super admin,admin,wakasek'])->group(function () {
        Route::get('konfirmasi-gms', [\App\Http\Controllers\KonfirmasiGmsController::class, 'index'])->name('konfirmasi-gms.index');
        Route::post('konfirmasi-gms', [\App\Http\Controllers\KonfirmasiGmsController::class, 'store'])->name('konfirmasi-gms.store');
        Route::post('konfirmasi-gms/bulk', [\App\Http\Controllers\KonfirmasiGmsController::class, 'bulkStore'])->name('konfirmasi-gms.bulk');
    });
});

require __DIR__.'/settings.php';
