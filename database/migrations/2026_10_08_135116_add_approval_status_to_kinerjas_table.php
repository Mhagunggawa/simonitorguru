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
            $table->string('status_tahap1')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kinerjas', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status_tahap1', 'approved_by', 'approved_at']);
        });
    }
};
