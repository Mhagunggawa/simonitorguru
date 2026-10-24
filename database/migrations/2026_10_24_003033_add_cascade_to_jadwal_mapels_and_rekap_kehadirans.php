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
        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->dropForeign(['jadwal_mapel_id']);
            $table->foreign('jadwal_mapel_id')
                ->references('id')
                ->on('jadwal_mapels')
                ->onDelete('cascade');
        });

        Schema::table('jadwal_mapels', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->foreign('guru_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_mapels', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->foreign('guru_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->dropForeign(['jadwal_mapel_id']);
            $table->foreign('jadwal_mapel_id')
                ->references('id')
                ->on('jadwal_mapels');
        });
    }
};
