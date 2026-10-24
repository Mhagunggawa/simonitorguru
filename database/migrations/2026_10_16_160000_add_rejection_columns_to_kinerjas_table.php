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
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->string('revisi_target')->nullable()->after('status_tahap2'); // 'guru', 'observer', 'keduanya'
            $table->text('catatan_revisi')->nullable()->after('revisi_target');
            $table->foreignId('rejected_by')->nullable()->after('catatan_revisi')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['revisi_target', 'catatan_revisi', 'rejected_by', 'rejected_at']);
        });
    }
};
