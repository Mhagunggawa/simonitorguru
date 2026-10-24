<?php

namespace App\Http\Controllers;

use App\Models\Titik;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TitikController extends Controller
{
    public function index(Request $request)
    {
        $query = Titik::with('jurusans');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_titik', 'like', "%{$search}%");
        }

        $titiks = $query->orderBy('nama_titik')->paginate(10)->withQueryString();

        return Inertia::render('titik/Index', [
            'titiks' => $titiks,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        return Inertia::render('titik/Create', [
            'jurusans' => $jurusans
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_titik' => 'required|string|max:255',
            'jurusan_ids' => 'required|array',
            'jurusan_ids.*' => 'exists:jurusans,id',
        ]);

        $titik = Titik::create(['nama_titik' => $validated['nama_titik']]);
        $titik->jurusans()->sync($validated['jurusan_ids']);

        return redirect()->route('titik.index');
    }

    public function edit(Titik $titik)
    {
        // Muat relasi jurusans agar frontend tahu jurusan apa saja yang sudah ter-check
        $titik->load('jurusans');
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        
        return Inertia::render('titik/Edit', [
            'titik' => $titik,
            'jurusans' => $jurusans
        ]);
    }

    public function update(Request $request, Titik $titik)
    {
        $validated = $request->validate([
            'nama_titik' => 'required|string|max:255',
            'jurusan_ids' => 'required|array',
            'jurusan_ids.*' => 'exists:jurusans,id',
        ]);

        $titik->update(['nama_titik' => $validated['nama_titik']]);
        $titik->jurusans()->sync($validated['jurusan_ids']);

        return redirect()->route('titik.index');
    }

    public function destroy(Titik $titik)
    {
        $titik->delete();
        return redirect()->route('titik.index');
    }
}
