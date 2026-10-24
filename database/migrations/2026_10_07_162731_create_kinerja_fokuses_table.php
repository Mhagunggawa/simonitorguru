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
        Schema::create('kinerja_fokuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kinerja_id')->constrained('kinerjas')->onDelete('cascade');
            $table->text('nama_fokus')->nullable();
            $table->json('perilaku_dianjurkan')->nullable();
            $table->json('perilaku_dihindari')->nullable();
            $table->integer('penilaian')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kinerja_fokuses');
    }
};
