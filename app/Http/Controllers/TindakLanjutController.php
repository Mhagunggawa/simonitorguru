<?php

namespace App\Http\Controllers;

use App\Models\TindakLanjutEvaluasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SuratPeringatanMail;
use Illuminate\Support\Facades\Storage;

class TindakLanjutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipe_peringatan' => 'required|in:SP 1,SP 2,SP 3',
            'file_dokumen' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $guru = User::findOrFail($request->user_id);

        if ($request->tipe_peringatan === 'SP 2') {
            $hasSp1 = TindakLanjutEvaluasi::where('user_id', $guru->id)
                ->where('tipe_peringatan', 'SP 1')
                ->exists();
            if (! $hasSp1) {
                return redirect()->back()->withErrors([
                    'file_dokumen' => 'SP 1 harus diterbitkan terlebih dahulu sebelum dapat mengirimkan SP 2.',
                ]);
            }
        }

        if ($request->tipe_peringatan === 'SP 3') {
            $hasSp2 = TindakLanjutEvaluasi::where('user_id', $guru->id)
                ->where('tipe_peringatan', 'SP 2')
                ->exists();
            if (! $hasSp2) {
                return redirect()->back()->withErrors([
                    'file_dokumen' => 'SP 2 harus diterbitkan terlebih dahulu sebelum dapat mengirimkan Panggilan Kepsek (SP 3).',
                ]);
            }
        }

        $path = $request->file('file_dokumen')->store('surat_peringatan', 'public');

        TindakLanjutEvaluasi::create([
            'user_id' => $guru->id,
            'tipe_peringatan' => $request->tipe_peringatan,
            'file_dokumen' => $path,
        ]);

        // Send Email via Queue
        Mail::to($guru->email)->queue(new SuratPeringatanMail($guru, $request->tipe_peringatan, $path));

        \App\Helpers\ActivityLogger::log('CREATE', "Mengirimkan {$request->tipe_peringatan} ke guru {$guru->name}");

        return redirect()->back()->with('success', "{$request->tipe_peringatan} berhasil dikirim ke {$guru->name}.");
    }

    public function destroy($id)
    {
        $sp = TindakLanjutEvaluasi::with('user')->findOrFail($id);
        $guruName = $sp->user?->name ?? 'Guru';
        $tipe = $sp->tipe_peringatan;

        if ($sp->file_dokumen && Storage::disk('public')->exists($sp->file_dokumen)) {
            Storage::disk('public')->delete($sp->file_dokumen);
        }

        $sp->delete();

        \App\Helpers\ActivityLogger::log('DELETE', "Membatalkan/menghapus {$tipe} untuk {$guruName}");

        return redirect()->back()->with('success', "{$tipe} untuk {$guruName} berhasil dibatalkan/dihapus.");
    }
}
