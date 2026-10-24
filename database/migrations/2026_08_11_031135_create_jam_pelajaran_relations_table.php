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
        // 1. Create pivot for jadwal_mapels
        Schema::create('jadwal_mapel_jam_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_mapel_id')->constrained('jadwal_mapels')->onDelete('cascade');
            $table->foreignId('jam_pelajaran_id')->constrained('jam_pelajarans')->onDelete('cascade');
        });

        // 2. Create pivot for jadwal_pikets
        Schema::create('jadwal_piket_jam_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_piket_id')->constrained('jadwal_pikets')->onDelete('cascade');
            $table->foreignId('jam_pelajaran_id')->constrained('jam_pelajarans')->onDelete('cascade');
        });

        // 3. Remove jam_pelajaran_id from jadwal_mapels
        Schema::table('jadwal_mapels', function (Blueprint $table) {
            $table->dropForeign(['jam_pelajaran_id']);
            $table->dropColumn('jam_pelajaran_id');
        });

        // 4. Remove jam_pelajaran_id from jadwal_pikets
        Schema::table('jadwal_pikets', function (Blueprint $table) {
            $table->dropForeign(['jam_pelajaran_id']);
            $table->dropColumn('jam_pelajaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_pikets', function (Blueprint $table) {
            $table->foreignId('jam_pelajaran_id')->nullable()->constrained('jam_pelajarans')->onDelete('cascade');
        });

        Schema::table('jadwal_mapels', function (Blueprint $table) {
            $table->foreignId('jam_pelajaran_id')->nullable()->constrained('jam_pelajarans')->onDelete('cascade');
        });

        Schema::dropIfExists('jadwal_piket_jam_pelajaran');
        Schema::dropIfExists('jadwal_mapel_jam_pelajaran');
    }
};
