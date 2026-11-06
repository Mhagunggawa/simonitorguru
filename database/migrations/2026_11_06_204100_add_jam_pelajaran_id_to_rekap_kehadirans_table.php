<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\RekapKehadiran;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->foreignId('jam_pelajaran_id')
                ->nullable()
                ->after('jadwal_piket_id')
                ->constrained('jam_pelajarans')
                ->nullOnDelete();
        });

        // Populate jam_pelajaran_id for existing records
        try {
            $rekaps = RekapKehadiran::with(['jadwalPiket.jamPelajarans', 'jadwalMapel.jamPelajarans'])->get();
            foreach ($rekaps as $rekap) {
                $piketJamIds = $rekap->jadwalPiket?->jamPelajarans?->pluck('id')->toArray() ?? [];
                $mapelJamIds = $rekap->jadwalMapel?->jamPelajarans?->pluck('id')->toArray() ?? [];

                // Cari irisan antara jam piket dan jam mapel
                $intersect = array_intersect($piketJamIds, $mapelJamIds);
                $matchedJamId = !empty($intersect) ? reset($intersect) : (!empty($mapelJamIds) ? reset($mapelJamIds) : (!empty($piketJamIds) ? reset($piketJamIds) : null));

                if ($matchedJamId) {
                    DB::table('rekap_kehadirans')
                        ->where('id', $rekap->id)
                        ->update(['jam_pelajaran_id' => $matchedJamId]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if seeding data has issues
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_kehadirans', function (Blueprint $table) {
            $table->dropForeign(['jam_pelajaran_id']);
            $table->dropColumn('jam_pelajaran_id');
        });
    }
};

