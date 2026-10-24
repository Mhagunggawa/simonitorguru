<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DataPurgeService
{
    /**
     * Hanya tabel transaksi operasional harian yang dibersihkan.
     * Seluruh data master (pengguna, guru, kelas, jurusan, mapel, jam pelajaran),
     * seluruh jadwal (jadwal mapel, jadwal gms, jadwal piket), kinerja guru,
     * dan pengaturan sistem TETAP AMAN dan dipertahankan.
     *
     * @var list<string>
     */
    private const TRANSACTION_TABLES = [
        'rekap_kehadirans',
        'absensi_gms',
        'absensi_upacaras',
        'izin_pikets',
        'tindak_lanjut_evaluasis',
        'activity_logs',
    ];

    public function purge(int|string|null $currentUserId): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TRANSACTION_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Bersihkan berkas dokumentasi & foto bukti transaksi operasional
        Storage::disk('public')->deleteDirectory('absensi-gms');
        Storage::disk('public')->deleteDirectory('pelaporan');
        Storage::disk('public')->deleteDirectory('surat_peringatan');
        // Catatan: direktori 'logos' TIDAK dihapus agar identitas aplikasi tetap terjaga
    }
}
