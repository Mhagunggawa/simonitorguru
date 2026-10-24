<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\JadwalMapel;
use App\Models\RekapKehadiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\IzinPiket;
use App\Services\ImageCompressor;

class PelaporanPiketController extends Controller
{
    public function index()
    {
        $jadwalPikets = JadwalPiket::with(['jamPelajarans', 'titik'])
            ->where('guru_piket_id', auth()->id())
            ->where(function($q) {
                $q->whereDate('tanggal_piket', today())
                  ->orWhereIn('id', IzinPiket::where('guru_piket_id', auth()->id())
                                      ->where('status', 'approved')
                                      ->pluck('jadwal_piket_id'));
            })
            ->get();

        // Filter out jam_pelajarans that are already fully reported
        foreach ($jadwalPikets as $jp) {
            $hariPiket = Carbon::parse($jp->tanggal_piket)->locale('id')->isoFormat('dddd');
            $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $jp->id)->pluck('jadwal_mapel_id');
            
            $jp->setRelation('jamPelajarans', $jp->jamPelajarans->filter(function($jam) use ($jp, $hariPiket, $reportedMapelIds) {
                $unreportedCount = JadwalMapel::where('hari', $hariPiket)
                    ->whereHas('jamPelajarans', function($q) use ($jam) {
                        $q->where('jam_pelajarans.id', $jam->id);
                    })
                    ->whereHas('kelas.jurusan.titiks', function($q) use ($jp) {
                        $q->where('titiks.id', $jp->titik_id);
                    })
                    ->whereNotIn('id', $reportedMapelIds)
                    ->count();
                
                return $unreportedCount > 0;
            })->values());
        }

        // Only keep jadwal_pikets that still have jam_pelajarans to report
        $jadwalPikets = $jadwalPikets->filter(function($jp) {
            return $jp->jamPelajarans->count() > 0;
        })->values();

        return Inertia::render('guru-piket/Index', [
            'jadwalPikets' => $jadwalPikets,
            'serverTime' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function create(Request $request, $id)
    {
        $jadwalPiket = JadwalPiket::with(['jamPelajarans', 'titik'])->findOrFail($id);
        
        $jamPelajaranId = $request->query('jam_pelajaran_id');
        if (!$jamPelajaranId) {
            abort(400, 'Jam Pelajaran ID is required');
        }

        $currentJam = $jadwalPiket->jamPelajarans->where('id', $jamPelajaranId)->first();
        if (!$currentJam) {
            abort(403, 'Jam Pelajaran ini tidak ada di jadwal piket Anda.');
        }

        $now = Carbon::now();
        
        $mulaiStr = strlen($currentJam->waktu_mulai) <= 5 ? $currentJam->waktu_mulai . ':00' : $currentJam->waktu_mulai;
        $selesaiStr = strlen($currentJam->waktu_selesai) <= 5 ? $currentJam->waktu_selesai . ':00' : $currentJam->waktu_selesai;
        
        $startTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $endTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $selesaiStr);

        // Check if report time has passed
        $isTerlambat = false;
        if ($now->greaterThan($endTime)) {
            $izin = IzinPiket::where('jadwal_piket_id', $jadwalPiket->id)
                ->where('jam_pelajaran_id', $jamPelajaranId)
                ->where('guru_piket_id', auth()->id())
                ->first();

            if (!$izin || $izin->status !== 'approved') {
                if (!$izin) {
                    IzinPiket::create([
                        'jadwal_piket_id' => $jadwalPiket->id,
                        'jam_pelajaran_id' => $jamPelajaranId,
                        'guru_piket_id' => auth()->id(),
                        'status' => 'pending',
                    ]);
                }
                return redirect()->route('pelaporan-piket.index')->with('error', 'Anda terlambat melakukan pelaporan. Permohonan izin telah dikirim. Silakan hubungi Wakasek untuk mendapatkan akses.');
            }
            $isTerlambat = true;
        } else if ($now->lessThan($startTime)) {
            // Hanya validasi jika waktu belum dimulai
            return redirect()->route('pelaporan-piket.index')->with('error', 'Waktu pengisian laporan untuk jam ini belum dimulai.');
        }

        // Ambil hari berdasarkan tanggal_piket
        $hariPiket = Carbon::parse($jadwalPiket->tanggal_piket)->locale('id')->isoFormat('dddd');

        // Cari jadwal mapel di hari tersebut, jam tersebut, dan titik tersebut
        $baseQuery = JadwalMapel::with(['kelas', 'mataPelajaran', 'guru'])
            ->where('hari', $hariPiket)
            ->whereHas('jamPelajarans', function($q) use ($jamPelajaranId) {
                $q->where('jam_pelajarans.id', $jamPelajaranId);
            })
            ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                $q->where('titiks.id', $jadwalPiket->titik_id);
            });

        $totalClasses = $baseQuery->count();
        $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $jadwalPiket->id)->pluck('jadwal_mapel_id');
        $jadwalMapels = $baseQuery->whereNotIn('id', $reportedMapelIds)->get();

        return Inertia::render('guru-piket/Create', [
            'jadwalPiket' => $jadwalPiket,
            'jadwalMapels' => $jadwalMapels,
            'totalClasses' => $totalClasses,
            'currentJam' => $currentJam,
            'isTerlambat' => $isTerlambat
        ]);
    }

    public function store(Request $request)
    {
        $jadwalPiket = JadwalPiket::with('jamPelajarans')->findOrFail($request->jadwal_piket_id);

        if ($jadwalPiket->guru_piket_id !== Auth::id()) {
            abort(403);
        }

        $currentJam = $jadwalPiket->jamPelajarans->where('id', $request->jam_pelajaran_id)->first();
        if (!$currentJam) {
            abort(403, 'Jam Pelajaran ini tidak ada di jadwal piket Anda.');
        }

        $currentTime = Carbon::now();
        $mulaiStr = strlen($currentJam->waktu_mulai) <= 5 ? $currentJam->waktu_mulai . ':00' : $currentJam->waktu_mulai;
        $selesaiStr = strlen($currentJam->waktu_selesai) <= 5 ? $currentJam->waktu_selesai . ':00' : $currentJam->waktu_selesai;

        $waktuMulai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $waktuSelesai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $selesaiStr);

        $isTerlambat = false;
        $izin = null;
        if ($currentTime->greaterThan($waktuSelesai)) {
            $izin = IzinPiket::where('jadwal_piket_id', $request->jadwal_piket_id)
                ->where('jam_pelajaran_id', $request->jam_pelajaran_id)
                ->where('guru_piket_id', auth()->id())
                ->first();

            if (!$izin || $izin->status !== 'approved') {
                return back()->withErrors(['error' => 'Waktu pelaporan di luar batas dan izin belum disetujui.']);
            }
            $isTerlambat = true;
        } else if ($currentTime->lessThan($waktuMulai)) {
             return back()->withErrors(['error' => 'Waktu pelaporan belum dimulai.']);
        }

        $rules = [
            'jadwal_piket_id' => 'required|exists:jadwal_pikets,id',
            'jam_pelajaran_id' => 'required|exists:jam_pelajarans,id',
            'jadwal_mapel_id' => [
                'required',
                'exists:jadwal_mapels,id',
                Rule::unique('rekap_kehadirans')->where(function ($query) use ($request) {
                    return $query->where('jadwal_piket_id', $request->jadwal_piket_id);
                })
            ],
            'status_kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpha,Kosong',
            'foto_dokumentasi' => $isTerlambat ? 'nullable|image|max:5120' : 'required|image|max:5120',
            'keterangan' => 'nullable|string',
        ];

        $messages = [
            'jadwal_mapel_id.unique' => 'Kelas ini sudah dilaporkan pada shift ini.',
            'foto_dokumentasi.required' => 'Foto dokumentasi wajib diisi untuk pelaporan tepat waktu.'
        ];

        $request->validate($rules, $messages);

        $path = null;
        if ($request->hasFile('foto_dokumentasi')) {
            $path = ImageCompressor::storeCompressed($request->file('foto_dokumentasi'), 'pelaporan', 100);
        }

        RekapKehadiran::create([
            'jadwal_mapel_id' => $request->jadwal_mapel_id,
            'jadwal_piket_id' => $request->jadwal_piket_id,
            'status_kehadiran' => $request->status_kehadiran,
            'foto_dokumentasi' => $path,
            'keterangan' => $request->keterangan,
            'is_terlambat' => $isTerlambat,
        ]);

        $hariPiket = Carbon::parse($jadwalPiket->tanggal_piket)->locale('id')->isoFormat('dddd');
        $baseQuery = JadwalMapel::where('hari', $hariPiket)
            ->whereHas('jamPelajarans', function($q) use ($request) {
                $q->where('jam_pelajarans.id', $request->jam_pelajaran_id);
            })
            ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                $q->where('titiks.id', $jadwalPiket->titik_id);
            });
        
        $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $request->jadwal_piket_id)->pluck('jadwal_mapel_id');
        $unreportedCount = $baseQuery->whereNotIn('id', $reportedMapelIds)->count();

        if ($unreportedCount == 0) {
            if ($isTerlambat && $izin) {
                $izin->update(['status' => 'completed']);
            }
            return redirect()->route('pelaporan-piket.index')->with('success', 'Laporan kehadiran berhasil disimpan. Semua kelas untuk jam ini telah selesai dilaporkan.');
        } else {
            return redirect()->route('pelaporan-piket.index')
                ->with('success', 'Laporan kehadiran berhasil disimpan. Masih tersisa ' . $unreportedCount . ' kelas lainnya pada jam ini.');
        }
    }
}
