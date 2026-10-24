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
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha'])->default('Hadir')->change();
            if (!Schema::hasColumn('absensi_gms', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('foto_bukti');
            }
            if (!Schema::hasColumn('absensi_gms', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->after('keterangan')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_gms', function (Blueprint $table) {
            $table->enum('status', ['Hadir', 'Alpha'])->default('Hadir')->change();
            if (Schema::hasColumn('absensi_gms', 'confirmed_by')) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn('confirmed_by');
            }
            if (Schema::hasColumn('absensi_gms', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};
