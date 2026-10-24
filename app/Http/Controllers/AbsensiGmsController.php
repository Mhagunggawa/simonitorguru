<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsensiGms;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageCompressor;

class AbsensiGmsController extends Controller
{
    public function create()
    {
        $now = Carbon::now();
        $pengaturan = \App\Models\PengaturanGms::first();
        $startTimeStr = $pengaturan ? $pengaturan->waktu_mulai : '07:15:00';
        $endTimeStr = $pengaturan ? $pengaturan->waktu_selesai : '08:00:00';
        
        $startTime = Carbon::createFromFormat('H:i:s', $startTimeStr);
        $endTime = Carbon::createFromFormat('H:i:s', $endTimeStr);

        $hariMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        $hariIni = $hariMap[$now->format('l')];
        $hasJadwal = auth()->user()->jadwalGms()->where('hari', $hariIni)->exists();

        // Check if user already submitted today
        $alreadySubmitted = AbsensiGms::where('user_id', auth()->id())
            ->whereDate('tanggal', $now->toDateString())
            ->exists();

        return Inertia::render('absensi-gms/Create', [
            'serverTime' => $now->toTimeString(),
            'isOpen' => $now->between($startTime, $endTime),
            'alreadySubmitted' => $alreadySubmitted,
            'currentDate' => $now->translatedFormat('l, d F Y'),
            'hasJadwal' => $hasJadwal
        ]);
    }

    public function store(Request $request)
    {
        $now = Carbon::now();
        $pengaturan = \App\Models\PengaturanGms::first();
        $startTimeStr = $pengaturan ? $pengaturan->waktu_mulai : '07:15:00';
        $endTimeStr = $pengaturan ? $pengaturan->waktu_selesai : '08:00:00';
        
        $startTime = Carbon::createFromFormat('H:i:s', $startTimeStr);
        $endTime = Carbon::createFromFormat('H:i:s', $endTimeStr);

        $hariMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        $hariIni = $hariMap[$now->format('l')];
        $hasJadwal = auth()->user()->jadwalGms()->where('hari', $hariIni)->exists();

        if (!$hasJadwal) {
            return back()->with('error', 'Anda tidak memiliki jadwal giliran Absensi GMS pada hari ini.');
        }

        if (!$now->between($startTime, $endTime)) {
            $formattedStart = Carbon::createFromFormat('H:i:s', $startTimeStr)->format('H:i');
            $formattedEnd = Carbon::createFromFormat('H:i:s', $endTimeStr)->format('H:i');
            return back()->with('error', "Sesi absensi GMS saat ini tidak aktif (hanya {$formattedStart} - {$formattedEnd}).");
        }

        $alreadySubmitted = AbsensiGms::where('user_id', auth()->id())
            ->whereDate('tanggal', $now->toDateString())
            ->exists();
            
        if ($alreadySubmitted) {
            return back()->with('error', 'Anda sudah melakukan absensi GMS hari ini.');
        }

        $request->validate([
            'foto_bukti' => 'required|image|max:5120', // Max 5MB
        ]);

        $path = ImageCompressor::storeCompressed($request->file('foto_bukti'), 'absensi-gms', 100);

        AbsensiGms::create([
            'user_id' => auth()->id(),
            'tanggal' => $now->toDateString(),
            'waktu_absen' => $now->toTimeString(),
            'status' => 'Hadir',
            'foto_bukti' => $path,
        ]);

        return redirect()->route('dashboard')->with('success', 'Absensi GMS berhasil dicatat.');
    }
}
