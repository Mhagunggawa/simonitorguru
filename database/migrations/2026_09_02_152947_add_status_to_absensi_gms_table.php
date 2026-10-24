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
        Schema::table('absensi_gms', function (Blueprint $table) {
            $table->enum('status', ['Hadir', 'Alpha'])->default('Hadir')->after('waktu_absen');
            $table->time('waktu_absen')->nullable()->change();
            $table->string('foto_bukti')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_gms', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->time('waktu_absen')->nullable(false)->change();
            $table->string('foto_bukti')->nullable(false)->change();
        });
    }
};
