<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JurusanController extends Controller
{
    public function index(Request $request)
    {
        $query = Jurusan::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_jurusan', 'like', "%{$search}%");
        }

        $jurusans = $query->orderBy('nama_jurusan')->paginate(10)->withQueryString();

        return Inertia::render('jurusan/Index', [
            'jurusans' => $jurusans,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('jurusan/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jurusan' => 'required|string|max:255|unique:jurusans,nama_jurusan',
        ]);

        Jurusan::create($validated);

        return redirect()->route('jurusan.index');
    }

    public function edit(Jurusan $jurusan)
    {
        return Inertia::render('jurusan/Edit', [
            'jurusan' => $jurusan
        ]);
    }

    public function update(Request $request, Jurusan $jurusan)
    {
        $validated = $request->validate([
            'nama_jurusan' => 'required|string|max:255|unique:jurusans,nama_jurusan,' . $jurusan->id,
        ]);

        $jurusan->update($validated);

        return redirect()->route('jurusan.index');
    }

    public function destroy(Jurusan $jurusan)
    {
        $jurusan->delete();
        return redirect()->route('jurusan.index');
    }
}
