<?php

namespace App\Http\Controllers;

use App\Models\JadwalMapel;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\JamPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class JadwalMapelController extends Controller
{
    public function index()
    {
        $jadwalMapels = JadwalMapel::with(['kelas', 'mataPelajaran', 'jamPelajarans', 'guru'])->get();
        return Inertia::render('jadwal-mapel/Index', [
            'jadwalMapels' => $jadwalMapels,
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('jadwal-mapel/Create', [
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
            'mataPelajarans' => MataPelajaran::orderBy('nama_mapel')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get(),
            'gurus' => User::where('role', 'guru')->orderBy('name')->get()
        ]);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'hari' => 'required|string'
        ]);

        $jadwalMapels = JadwalMapel::with('jamPelajarans')
            ->where('kelas_id', $request->kelas_id)
            ->where('hari', $request->hari)
            ->get();
            
        return response()->json($jadwalMapels);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jadwal' => 'array',
            'jadwal.*.jam_pelajaran_id' => 'required|exists:jam_pelajarans,id',
            'jadwal.*.mata_pelajaran_id' => 'nullable|exists:mata_pelajarans,id',
            'jadwal.*.guru_id' => 'nullable|exists:users,id',
        ]);

        DB::transaction(function () use ($validated) {
            $kelasId = $validated['kelas_id'];
            $hari = $validated['hari'];
            $jadwalItems = $validated['jadwal'] ?? [];

            // 1. Detach all jam_pelajarans from existing JadwalMapel for this class & day
            $existingJadwals = JadwalMapel::where('kelas_id', $kelasId)
                ->where('hari', $hari)
                ->get();
                
            foreach ($existingJadwals as $jadwal) {
                $jadwal->jamPelajarans()->detach();
            }

            // 2. Group new incoming schedule by mapel and guru
            $grouped = [];
            foreach ($jadwalItems as $item) {
                if (!empty($item['mata_pelajaran_id']) && !empty($item['guru_id'])) {
                    $key = $item['mata_pelajaran_id'] . '_' . $item['guru_id'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'mata_pelajaran_id' => $item['mata_pelajaran_id'],
                            'guru_id' => $item['guru_id'],
                            'jam_pelajaran_ids' => []
                        ];
                    }
                    $grouped[$key]['jam_pelajaran_ids'][] = $item['jam_pelajaran_id'];
                }
            }

            // 3. Create or find JadwalMapel and attach jam_pelajarans
            foreach ($grouped as $group) {
                $jadwal = JadwalMapel::firstOrCreate([
                    'kelas_id' => $kelasId,
                    'hari' => $hari,
                    'mata_pelajaran_id' => $group['mata_pelajaran_id'],
                    'guru_id' => $group['guru_id'],
                ]);
                // Attach the new jam_pelajarans
                $jadwal->jamPelajarans()->attach($group['jam_pelajaran_ids']);
            }
        });

        return redirect()->route('jadwal-mapel.index')->with('success', 'Jadwal Mata Pelajaran berhasil diperbarui.');
    }
}
