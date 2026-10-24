<?php

namespace App\Http\Controllers;

use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JamPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $query = JamPelajaran::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('urutan_jam', 'like', "%{$search}%")
                  ->orWhere('waktu_mulai', 'like', "%{$search}%")
                  ->orWhere('waktu_selesai', 'like', "%{$search}%");
        }

        $jamPelajarans = $query->orderBy('urutan_jam')->paginate(10)->withQueryString();

        return Inertia::render('jam-pelajaran/Index', [
            'jamPelajarans' => $jamPelajarans,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('jam-pelajaran/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'urutan_jam' => 'required|integer',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required|after:waktu_mulai',
        ]);

        JamPelajaran::create($validated);

        return redirect()->route('jam-pelajaran.index');
    }

    public function edit(JamPelajaran $jamPelajaran)
    {
        return Inertia::render('jam-pelajaran/Edit', [
            'jamPelajaran' => $jamPelajaran
        ]);
    }

    public function update(Request $request, JamPelajaran $jamPelajaran)
    {
        $validated = $request->validate([
            'urutan_jam' => 'required|integer',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required|after:waktu_mulai',
        ]);

        $jamPelajaran->update($validated);

        return redirect()->route('jam-pelajaran.index');
    }

    public function destroy(JamPelajaran $jamPelajaran)
    {
        $jamPelajaran->delete();
        return redirect()->route('jam-pelajaran.index');
    }
}
