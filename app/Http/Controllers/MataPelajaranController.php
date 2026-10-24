<?php

namespace App\Http\Controllers;

use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MataPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $query = MataPelajaran::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_mapel', 'like', "%{$search}%");
        }

        $mataPelajarans = $query->orderBy('nama_mapel')->paginate(10)->withQueryString();

        return Inertia::render('mata-pelajaran/Index', [
            'mataPelajarans' => $mataPelajarans,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('mata-pelajaran/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mapels' => 'required|array|min:1',
            'mapels.*.nama_mapel' => 'required|string|max:255',
        ]);

        foreach ($validated['mapels'] as $mapel) {
            MataPelajaran::create(['nama_mapel' => $mapel['nama_mapel']]);
        }

        return redirect()->route('mata-pelajaran.index')->with('success', 'Mata Pelajaran created successfully.');
    }

    public function edit(MataPelajaran $mataPelajaran)
    {
        return Inertia::render('mata-pelajaran/Edit', [
            'mataPelajaran' => $mataPelajaran
        ]);
    }

    public function update(Request $request, MataPelajaran $mataPelajaran)
    {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:255',
        ]);

        $mataPelajaran->update($validated);

        return redirect()->route('mata-pelajaran.index')->with('success', 'Mata Pelajaran updated successfully.');
    }

    public function destroy(MataPelajaran $mataPelajaran)
    {
        $mataPelajaran->delete();
        return redirect()->route('mata-pelajaran.index')->with('success', 'Mata Pelajaran deleted successfully.');
    }
}
