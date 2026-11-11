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
        Schema::table('pengaturan_gms', function (Blueprint $table) {
            $table->time('jam_normal_mulai')->nullable()->after('waktu_selesai');
            $table->time('jam_normal_selesai')->nullable()->after('jam_normal_mulai');
            $table->time('jam_terlambat_mulai')->nullable()->after('jam_normal_selesai');
            $table->time('jam_terlambat_selesai')->nullable()->after('jam_terlambat_mulai');
        });

        Schema::table('absensi_gms', function (Blueprint $table) {
            $table->boolean('is_terlambat')->default(false)->after('status');
        });

        // Set default values for existing pengaturan_gms row
        $pengaturan = \App\Models\PengaturanGms::first();
        if ($pengaturan) {
            $pengaturan->update([
                'jam_normal_mulai' => $pengaturan->waktu_mulai ?: '07:15:00',
                'jam_normal_selesai' => '07:30:00',
                'jam_terlambat_mulai' => '07:31:00',
                'jam_terlambat_selesai' => $pengaturan->waktu_selesai ?: '08:00:00',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_gms', function (Blueprint $table) {
            $table->dropColumn([
                'jam_normal_mulai',
                'jam_normal_selesai',
                'jam_terlambat_mulai',
                'jam_terlambat_selesai',
            ]);
        });

        Schema::table('absensi_gms', function (Blueprint $table) {
            $table->dropColumn('is_terlambat');
        });
    }
};

