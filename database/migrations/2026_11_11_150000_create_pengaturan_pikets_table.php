<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaturan_pikets', function (Blueprint $table) {
            $table->id();
            $table->time('waktu_mulai')->default('06:30:00');
            $table->time('waktu_selesai')->default('16:00:00');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // Seed default row
        DB::table('pengaturan_pikets')->insert([
            'waktu_mulai' => '06:30:00',
            'waktu_selesai' => '16:00:00',
            'keterangan' => 'Batas waktu default pelaporan tugas piket harian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_pikets');
    }
};

