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
            $table->text('catatan_refleksi_tahap3')->nullable()->after('kesadaran_refleksi_tahap3');
            $table->text('catatan_upaya_tahap3')->nullable()->after('upaya_kompetensi_tahap3');
            $table->text('perubahan_praktik_kinerja_tahap3')->nullable()->after('upaya_kompetensi_tahap3');
            $table->text('catatan_peningkatan_tahap3')->nullable()->after('peningkatan_kinerja_tahap3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropColumn([
                'catatan_refleksi_tahap3',
                'catatan_upaya_tahap3',
                'perubahan_praktik_kinerja_tahap3',
                'catatan_peningkatan_tahap3',
            ]);
        });
    }
};
