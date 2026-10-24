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
            $table->string('tempat_tahap2')->nullable();
            $table->date('tanggal_tahap2')->nullable();
            $table->time('waktu_tahap2')->nullable();
            $table->text('refleksi_rgtk')->nullable();
            $table->text('refleksi_lain')->nullable();
            $table->json('tujuan_tindak_lanjut')->nullable();
            $table->json('upaya_tindak_lanjut')->nullable();
            $table->json('kapan_tindak_lanjut')->nullable();
            $table->text('kebutuhan_dukungan')->nullable();
            $table->json('pengembangan_kompetensi')->nullable();
            $table->json('tugas_tambahan')->nullable();
            $table->text('catatan_kepsek_tahap2')->nullable();
            $table->tinyInteger('kesadaran_refleksi')->nullable();
            $table->string('status_tahap2')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropColumn([
                'tempat_tahap2', 'tanggal_tahap2', 'waktu_tahap2', 
                'refleksi_rgtk', 'refleksi_lain', 'tujuan_tindak_lanjut',
                'upaya_tindak_lanjut', 'kapan_tindak_lanjut', 'kebutuhan_dukungan',
                'pengembangan_kompetensi', 'tugas_tambahan', 'catatan_kepsek_tahap2',
                'kesadaran_refleksi', 'status_tahap2'
            ]);
        });
    }
};
