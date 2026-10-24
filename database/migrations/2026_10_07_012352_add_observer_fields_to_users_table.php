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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_observer')->default(false)->after('role');
            $table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete()->after('is_observer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['observer_id']);
            $table->dropColumn(['is_observer', 'observer_id']);
        });
    }
};
