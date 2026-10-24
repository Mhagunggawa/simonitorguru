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
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super admin', 'admin', 'wakasek', 'guru', 'kepsek') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'wakasek', 'guru', 'kepsek') NOT NULL");
    }
};
