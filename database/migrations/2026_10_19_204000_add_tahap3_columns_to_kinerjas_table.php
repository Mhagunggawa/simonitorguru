<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            // Div 1: Jadwal oleh Observer
            $table->string('tempat_tahap3')->nullable()->after('status_tahap2');
            $table->date('tanggal_tahap3')->nullable()->after('tempat_tahap3');
            $table->time('waktu_tahap3')->nullable()->after('tanggal_tahap3');

            // Div 2: Tantangan, Kesadaran Refleksi, Inspirasi, Upaya Kompetensi
            $table->json('tantangan_perubahan')->nullable()->after('waktu_tahap3'); // Diisi Guru (array)
            $table->tinyInteger('kesadaran_refleksi_tahap3')->nullable()->after('tantangan_perubahan'); // Diisi Observer (1, 2, 3)
            $table->json('inspirasi_didapat')->nullable()->after('kesadaran_refleksi_tahap3'); // Diisi Guru (array)
            $table->text('perubahan_praktik_tahap3')->nullable()->after('inspirasi_didapat'); // Diisi Guru (textarea)
            $table->tinyInteger('upaya_kompetensi_tahap3')->nullable()->after('perubahan_praktik_tahap3'); // Diisi Observer (1, 2, 3)

            // Div 3: Rencana Mengatasi Tantangan & Peningkatan Kinerja
            $table->json('rencana_mengatasi_tantangan')->nullable()->after('upaya_kompetensi_tahap3'); // Diisi Guru (array)
            $table->tinyInteger('peningkatan_kinerja_tahap3')->nullable()->after('rencana_mengatasi_tantangan'); // Diisi Observer (1, 2, 3)

            // Div 4: Catatan Akhir Observer
            $table->text('catatan_observer_tahap3')->nullable()->after('peningkatan_kinerja_tahap3'); // Diisi Observer (textarea)

            // Status Tahap 3
            $table->string('status_tahap3')->default('belum_mulai')->after('catatan_observer_tahap3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropColumn([
                'tempat_tahap3',
                'tanggal_tahap3',
                'waktu_tahap3',
                'tantangan_perubahan',
                'kesadaran_refleksi_tahap3',
                'inspirasi_didapat',
                'perubahan_praktik_tahap3',
                'upaya_kompetensi_tahap3',
                'rencana_mengatasi_tantangan',
                'peningkatan_kinerja_tahap3',
                'catatan_observer_tahap3',
                'status_tahap3',
            ]);
        });
    }
};
