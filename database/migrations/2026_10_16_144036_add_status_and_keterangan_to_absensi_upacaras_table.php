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
        Schema::table('absensi_upacaras', function (Blueprint $table) {
            $table->string('status')->nullable()->after('is_hadir'); // 'Hadir', 'Alpha', 'Sakit', 'Izin'
            $table->text('keterangan')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_upacaras', function (Blueprint $table) {
            $table->dropColumn(['status', 'keterangan']);
        });
    }
};
