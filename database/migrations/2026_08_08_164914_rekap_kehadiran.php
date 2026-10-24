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
        Schema::create('rekap_kehadirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_mapel_id')->constrained('jadwal_mapels'); // Tahu jadwal kelas mana
            $table->foreignId('jadwal_piket_id')->constrained('jadwal_pikets'); // Tahu siapa guru piket yang melapor
            $table->enum('status_kehadiran', ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Kosong']);
            $table->string('foto_dokumentasi'); // Menyimpan path file gambar/foto
            $table->text('keterangan')->nullable(); // Opsional jika butuh catatan
            $table->timestamps(); // create_at bertindak sebagai waktu submit laporan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_kehadirans');
    }
};
