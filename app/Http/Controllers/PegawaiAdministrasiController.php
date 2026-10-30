<?php

namespace App\Http\Controllers;

use App\Exports\PegawaiAdministrasiTemplateExport;
use App\Imports\PegawaiAdministrasiImport;
use App\Models\PegawaiAdministrasi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiAdministrasiController extends Controller
{
    public function index(Request $request)
    {
        $query = PegawaiAdministrasi::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama', 'like', "%{$search}%");
        }

        $pegawais = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('pegawai-administrasi/Index', [
            'pegawais' => $pegawais,
            'filters' => $request->only('search'),
            'totalCount' => PegawaiAdministrasi::count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        PegawaiAdministrasi::create([
            'nama' => trim($validated['nama']),
        ]);

        return redirect()->route('pegawai-administrasi.index')->with('success', 'Pegawai administrasi berhasil ditambahkan.');
    }

    public function update(Request $request, PegawaiAdministrasi $pegawaiAdministrasi)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        $pegawaiAdministrasi->update([
            'nama' => trim($validated['nama']),
        ]);

        return redirect()->route('pegawai-administrasi.index')->with('success', 'Pegawai administrasi berhasil diperbarui.');
    }

    public function destroy(PegawaiAdministrasi $pegawaiAdministrasi)
    {
        $pegawaiAdministrasi->delete();

        return redirect()->route('pegawai-administrasi.index')->with('success', 'Pegawai administrasi berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $import = new PegawaiAdministrasiImport();
            Excel::import($import, $request->file('file'));

            $message = "Berhasil mengimpor {$import->importedCount} data pegawai administrasi.";
            if ($import->skippedCount > 0) {
                $message .= " ({$import->skippedCount} data diabaikan karena sudah ada atau duplikat).";
            }

            return redirect()->route('pegawai-administrasi.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('pegawai-administrasi.index')->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    public function template()
    {
        return Excel::download(new PegawaiAdministrasiTemplateExport, 'template_pegawai_administrasi.xlsx');
    }
}

