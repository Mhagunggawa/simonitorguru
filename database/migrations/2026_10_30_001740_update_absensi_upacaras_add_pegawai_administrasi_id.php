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
            $table->dropForeign(['user_id']);
        });

        Schema::table('absensi_upacaras', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('pegawai_administrasi_id')->nullable()->after('user_id')->constrained('pegawai_administrasis')->cascadeOnDelete();
            $table->unique(['tanggal', 'pegawai_administrasi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_upacaras', function (Blueprint $table) {
            $table->dropForeign(['pegawai_administrasi_id']);
            $table->dropUnique(['tanggal', 'pegawai_administrasi_id']);
            $table->dropColumn('pegawai_administrasi_id');

            $table->dropForeign(['user_id']);
        });

        Schema::table('absensi_upacaras', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
