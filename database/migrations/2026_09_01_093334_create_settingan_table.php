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
        Schema::create('settingan', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nama_aplikasi')->nullable()->default('Simonitorguru');
            $table->string('nama_instansi')->nullable();
            $table->string('logo_aplikasi')->nullable();
            $table->timestamp('last_backup_date')->nullable();
            $table->timestamp('last_mass_delete_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settingan');
    }
};
