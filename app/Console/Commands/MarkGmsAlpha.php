<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MarkGmsAlpha extends Command
{
    protected $signature = 'gms:mark-alpha';
    protected $description = 'Mark teachers as Alpha if they have GMS schedule today but did not absent';

    public function handle()
    {
        $now = \Carbon\Carbon::now();
        $todayStr = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        
        $hariMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        $hariIni = $hariMap[$now->format('l')] ?? null;
        if (!$hariIni) return;

        // Get all GMS schedules for today where waktu_selesai has passed
        $schedules = \App\Models\JadwalGms::where('hari', $hariIni)
            ->whereNotNull('waktu_selesai')
            ->where('waktu_selesai', '<', $currentTime)
            ->get();

        foreach ($schedules as $schedule) {
            $userId = $schedule->user_id;

            // Check if they have an AbsensiGms record for today
            $hasAbsen = \App\Models\AbsensiGms::where('user_id', $userId)
                ->whereDate('tanggal', $todayStr)
                ->exists();

            if (!$hasAbsen) {
                \App\Models\AbsensiGms::create([
                    'user_id' => $userId,
                    'tanggal' => $todayStr,
                    'waktu_absen' => null,
                    'foto_bukti' => null,
                    'status' => 'Alpha'
                ]);
                $this->info("Marked User ID {$userId} as Alpha for GMS today.");
            }
        }
    }
}
