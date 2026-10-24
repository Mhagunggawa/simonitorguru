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
            $table->boolean('is_terlambat')->default(false)->after('keterangan');
            $table->string('foto_dokumentasi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->dropColumn('is_terlambat');
            $table->string('foto_dokumentasi')->nullable(false)->change();
        });
    }
};
