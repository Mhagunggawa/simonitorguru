<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\Titik;
use App\Models\User;
use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class JadwalPiketController extends Controller
{
    public function index()
    {
        $jadwalPikets = JadwalPiket::with(['titik', 'guruPiket', 'jamPelajarans'])->latest('tanggal_piket')->paginate(10);
        return Inertia::render('jadwal-piket/Index', [
            'jadwalPikets' => $jadwalPikets
        ]);
    }

    public function create()
    {
        return Inertia::render('jadwal-piket/Create', [
            'titiks' => Titik::orderBy('nama_titik')->get(),
            'gurus' => User::where('role', 'guru')->whereHas('tipeGuru', function($q) {
                $q->where('akses_piket_kelas', true);
            })->orderBy('name')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateJadwalPiket($request);

        $jadwalPiket = JadwalPiket::create([
            'tanggal_piket' => $validated['tanggal_piket'],
            'titik_id' => $validated['titik_id'],
            'guru_piket_id' => $validated['guru_piket_id']
        ]);

        $jadwalPiket->jamPelajarans()->sync($validated['jam_pelajaran_ids']);

        return redirect()->route('jadwal-piket.index')->with('success', 'Jadwal Piket berhasil ditambahkan.');
    }

    public function edit(JadwalPiket $jadwalPiket)
    {
        $jadwalPiket->load('jamPelajarans');
        
        return Inertia::render('jadwal-piket/Edit', [
            'jadwalPiket' => $jadwalPiket,
            'titiks' => Titik::orderBy('nama_titik')->get(),
            'gurus' => User::where('role', 'guru')->whereHas('tipeGuru', function($q) {
                $q->where('akses_piket_kelas', true);
            })->orderBy('name')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get()
        ]);
    }

    public function update(Request $request, JadwalPiket $jadwalPiket)
    {
        $validated = $this->validateJadwalPiket($request, $jadwalPiket->id);

        $jadwalPiket->update([
            'tanggal_piket' => $validated['tanggal_piket'],
            'titik_id' => $validated['titik_id'],
            'guru_piket_id' => $validated['guru_piket_id']
        ]);

        $jadwalPiket->jamPelajarans()->sync($validated['jam_pelajaran_ids']);

        return redirect()->route('jadwal-piket.index')->with('success', 'Jadwal Piket berhasil diupdate.');
    }


    protected function validateJadwalPiket(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'tanggal_piket' => 'required|date',
            'titik_id' => 'required|exists:titiks,id',
            'guru_piket_id' => 'required|exists:users,id',
            'jam_pelajaran_ids' => 'required|array|min:1',
            'jam_pelajaran_ids.*' => 'exists:jam_pelajarans,id'
        ], [
            'jam_pelajaran_ids.required' => 'Pilih minimal satu jam pelajaran.',
            'jam_pelajaran_ids.min' => 'Pilih minimal satu jam pelajaran.',
        ]);

        $titikConflict = JadwalPiket::where('tanggal_piket', $validated['tanggal_piket'])
            ->where('titik_id', $validated['titik_id'])
            ->when($ignoreId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->whereHas('jamPelajarans', function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids']);
            })
            ->with(['guruPiket', 'titik', 'jamPelajarans' => function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids'])->orderBy('urutan_jam');
            }])
            ->first();

        if ($titikConflict) {
            $jamNames = $titikConflict->jamPelajarans->map(fn($j) => 'Jam ke-' . $j->urutan_jam)->join(', ');
            $isSameGuru = $titikConflict->guru_piket_id == $validated['guru_piket_id'];

            $errorMessage = $isSameGuru
                ? "Guru tersebut sudah memiliki jadwal piket di titik ini pada {$jamNames} untuk tanggal tersebut."
                : "Titik lokasi ini sudah dijadwalkan untuk {$titikConflict->guruPiket?->name} pada {$jamNames}. Jadwal piket pada titik dan jam yang sama tidak boleh bertabrakan.";

            throw \Illuminate\Validation\ValidationException::withMessages([
                'titik_id' => [$errorMessage],
                'jam_pelajaran_ids' => ["Bentrok jam pelajaran pada {$jamNames}."],
            ]);
        }

        $guruConflict = JadwalPiket::where('tanggal_piket', $validated['tanggal_piket'])
            ->where('guru_piket_id', $validated['guru_piket_id'])
            ->when($ignoreId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->whereHas('jamPelajarans', function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids']);
            })
            ->with(['titik', 'guruPiket', 'jamPelajarans' => function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids'])->orderBy('urutan_jam');
            }])
            ->first();

        if ($guruConflict) {
            $jamNames = $guruConflict->jamPelajarans->map(fn($j) => 'Jam ke-' . $j->urutan_jam)->join(', ');

            $errorMessage = "Guru ini sudah memiliki jadwal piket di {$guruConflict->titik?->nama_titik} pada {$jamNames} untuk tanggal tersebut.";

            throw \Illuminate\Validation\ValidationException::withMessages([
                'guru_piket_id' => [$errorMessage],
                'jam_pelajaran_ids' => ["Bentrok jam pelajaran pada {$jamNames}."],
            ]);
        }

        return $validated;
    }

    public function destroy(JadwalPiket $jadwalPiket)
    {
        foreach ($jadwalPiket->rekapKehadirans as $rekap) {
            if ($rekap->foto_dokumentasi && Storage::disk('public')->exists($rekap->foto_dokumentasi)) {
                Storage::disk('public')->delete($rekap->foto_dokumentasi);
            }
        }

        $jadwalPiket->delete();
        return redirect()->route('jadwal-piket.index')->with('success', 'Jadwal piket berhasil dihapus.');
    }
}
