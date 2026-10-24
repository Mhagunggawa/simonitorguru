<?php

namespace App\Http\Controllers;

use App\Models\TipeGuru;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TipeGuruController extends Controller
{
    public function index(Request $request)
    {
        $query = TipeGuru::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_tipe', 'like', "%{$search}%");
        }

        $tipeGurus = $query->paginate(10)->withQueryString();

        return Inertia::render('tipe-guru/Index', [
            'tipeGurus' => $tipeGurus,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('tipe-guru/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_tipe' => 'required|string|max:255',
            'akses_piket_kelas' => 'boolean',
            'akses_gms' => 'boolean',
        ]);

        $validated['akses_piket_kelas'] = $request->boolean('akses_piket_kelas');
        $validated['akses_gms'] = $request->boolean('akses_gms');

        TipeGuru::create($validated);

        return redirect()->route('tipe-guru.index')->with('success', 'Tipe Guru berhasil ditambahkan.');
    }

    public function edit(TipeGuru $tipeGuru)
    {
        return Inertia::render('tipe-guru/Edit', ['tipeGuru' => $tipeGuru]);
    }

    public function update(Request $request, TipeGuru $tipeGuru)
    {
        $validated = $request->validate([
            'nama_tipe' => 'required|string|max:255',
            'akses_piket_kelas' => 'boolean',
            'akses_gms' => 'boolean',
        ]);

        $validated['akses_piket_kelas'] = $request->boolean('akses_piket_kelas');
        $validated['akses_gms'] = $request->boolean('akses_gms');

        $tipeGuru->update($validated);

        return redirect()->route('tipe-guru.index')->with('success', 'Tipe Guru berhasil diperbarui.');
    }

    public function destroy(TipeGuru $tipeGuru)
    {
        $tipeGuru->delete();
        return redirect()->route('tipe-guru.index')->with('success', 'Tipe Guru berhasil dihapus.');
    }
}
