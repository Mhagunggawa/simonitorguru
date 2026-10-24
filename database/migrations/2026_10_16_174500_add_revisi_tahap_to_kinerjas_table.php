<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->tinyInteger('revisi_tahap')->nullable()->after('status_tahap2'); // 1 = Tahap 1, 2 = Tahap 2
        });
    }

    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropColumn('revisi_tahap');
        });
    }
};
