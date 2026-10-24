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
            $table->dropForeign(['jadwal_piket_id']);
            $table->foreign('jadwal_piket_id')
                ->references('id')
                ->on('jadwal_pikets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->dropForeign(['jadwal_piket_id']);
            $table->foreign('jadwal_piket_id')
                ->references('id')
                ->on('jadwal_pikets');
        });
    }
};
