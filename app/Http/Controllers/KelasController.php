<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\TingkatKelas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::with(['jurusan', 'tingkatKelas']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_kelas', 'like', "%{$search}%");
        }

        if ($request->filled('jurusan_id')) {
            $query->where('jurusan_id', $request->input('jurusan_id'));
        }

        $kelas = $query->orderBy('nama_kelas')->paginate(10)->withQueryString();
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        $tingkatKelas = TingkatKelas::orderBy('nama_tingkat')->get();

        return Inertia::render('kelas/Index', [
            'kelas' => $kelas,
            'jurusans' => $jurusans,
            'tingkatKelas' => $tingkatKelas,
            'filters' => $request->only(['search', 'jurusan_id']),
        ]);
    }

    public function create()
    {
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        $tingkatKelas = TingkatKelas::orderBy('nama_tingkat')->get();
        return Inertia::render('kelas/Create', [
            'jurusans' => $jurusans,
            'tingkatKelas' => $tingkatKelas
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat_kelas_id' => 'required|exists:tingkat_kelas,id',
        ]);

        Kelas::create($validated);

        return redirect()->route('kelas.index');
    }

    public function edit(Kelas $kelas)
    {
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        $tingkatKelas = TingkatKelas::orderBy('nama_tingkat')->get();
        return Inertia::render('kelas/Edit', [
            'kelas' => $kelas,
            'jurusans' => $jurusans,
            'tingkatKelas' => $tingkatKelas
        ]);
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat_kelas_id' => 'required|exists:tingkat_kelas,id',
        ]);

        $kelas->update($validated);

        return redirect()->route('kelas.index');
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();
        return redirect()->route('kelas.index');
    }
}
