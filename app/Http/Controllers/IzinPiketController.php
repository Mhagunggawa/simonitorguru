<?php

namespace App\Http\Controllers;

use App\Models\IzinPiket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IzinPiketController extends Controller
{
    public function index(Request $request)
    {
        // Auto-sync missed reports
        $now = \Carbon\Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $jadwalPikets = \App\Models\JadwalPiket::with('jamPelajarans')
            ->where('tanggal_piket', '<=', $today)
            ->get();

        foreach($jadwalPikets as $jp) {
            foreach($jp->jamPelajarans as $jam) {
                $waktuSelesai = $jam->waktu_selesai;
                if (strlen($waktuSelesai) <= 5) $waktuSelesai .= ':00';
                
                $endTime = \Carbon\Carbon::parse($jp->tanggal_piket . ' ' . $waktuSelesai);
                $isPast = $now->greaterThan($endTime);
                if ($isPast) {
                    $hariPiket = \Carbon\Carbon::parse($jp->tanggal_piket)->locale('id')->isoFormat('dddd');
                    $baseQuery = \App\Models\JadwalMapel::where('hari', $hariPiket)
                        ->whereHas('jamPelajarans', function($q) use ($jam) {
                            $q->where('jam_pelajarans.id', $jam->id);
                        })
                        ->whereHas('kelas.jurusan.titiks', function($q) use ($jp) {
                            $q->where('titiks.id', $jp->titik_id);
                        });
                    
                    if ($baseQuery->count() > 0) {
                        $reportedMapelIds = \App\Models\RekapKehadiran::where('jadwal_piket_id', $jp->id)->pluck('jadwal_mapel_id');
                        $unreportedCount = $baseQuery->whereNotIn('id', $reportedMapelIds)->count();

                        if ($unreportedCount > 0) {
                            \App\Models\IzinPiket::firstOrCreate([
                                'jadwal_piket_id' => $jp->id,
                                'jam_pelajaran_id' => $jam->id,
                                'guru_piket_id' => $jp->guru_piket_id,
                            ], [
                                'status' => 'pending'
                            ]);
                        }
                    }
                }
            }
        }

        $query = IzinPiket::with(['guruPiket', 'jamPelajaran', 'jadwalPiket.titik', 'jadwalPiket'])
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('guruPiket', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $izinPikets = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
            
        return Inertia::render('IzinPiket/Index', [
            'izinPikets' => $izinPikets,
            'filters' => [
                'search' => $request->input('search', '')
            ]
        ]);
    }

    public function approve(Request $request, $id)
    {
        $izin = IzinPiket::findOrFail($id);
        $izin->update(['status' => 'approved']);
        
        return redirect()->back()->with('success', 'Izin pelaporan susulan berhasil disetujui.');
    }
}
