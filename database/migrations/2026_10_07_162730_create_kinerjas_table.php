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
        Schema::create('kinerjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('observer_id')->constrained('users')->onDelete('cascade');
            $table->string('tempat')->nullable();
            $table->string('periode')->nullable();
            $table->date('tanggal')->nullable();
            $table->time('waktu')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->string('video_link')->nullable();
            $table->integer('tahap')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kinerjas');
    }
};
