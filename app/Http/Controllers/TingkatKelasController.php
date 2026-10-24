<?php
namespace App\Http\Controllers;

use App\Models\TingkatKelas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TingkatKelasController extends Controller
{
    public function index(Request $request)
    {
        $query = TingkatKelas::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_tingkat', 'like', "%{$search}%");
        }

        $tingkatKelas = $query->orderBy('nama_tingkat')->paginate(10)->withQueryString();

        return Inertia::render('TingkatKelas/Index', [
            'tingkatKelas' => $tingkatKelas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('TingkatKelas/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_tingkat' => 'required|string|max:255|unique:tingkat_kelas,nama_tingkat',
        ]);

        TingkatKelas::create($validated);

        return redirect()->route('tingkat-kelas.index')->with('success', 'Tingkat Kelas berhasil ditambahkan.');
    }

    public function edit(TingkatKelas $tingkatKela)
    {
        return Inertia::render('TingkatKelas/Edit', [
            'tingkat_kela' => $tingkatKela
        ]);
    }

    public function update(Request $request, TingkatKelas $tingkatKela)
    {
        $validated = $request->validate([
            'nama_tingkat' => 'required|string|max:255|unique:tingkat_kelas,nama_tingkat,' . $tingkatKela->id,
        ]);

        $tingkatKela->update($validated);

        return redirect()->route('tingkat-kelas.index')->with('success', 'Tingkat Kelas berhasil diperbarui.');
    }

    public function destroy(TingkatKelas $tingkatKela)
    {
        $tingkatKela->delete();

        return redirect()->route('tingkat-kelas.index')->with('success', 'Tingkat Kelas berhasil dihapus.');
    }
}
