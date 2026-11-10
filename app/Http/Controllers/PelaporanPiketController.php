<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\JadwalMapel;
use App\Models\RekapKehadiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\IzinPiket;
use App\Services\ImageCompressor;
use Illuminate\Support\Facades\Storage;

class PelaporanPiketController extends Controller
{
    public function index()
    {
        $jadwalPikets = JadwalPiket::with(['jamPelajarans', 'titik', 'rekapKehadirans'])
            ->where('guru_piket_id', auth()->id())
            ->where(function($q) {
                $q->whereDate('tanggal_piket', today())
                  ->orWhereIn('id', IzinPiket::where('guru_piket_id', auth()->id())
                                      ->where('status', 'approved')
                                      ->pluck('jadwal_piket_id'));
            })
            ->get();

        // Cari jadwal piket hari ini untuk opsi pengajuan Izin / Sakit
        $todayPiket = $jadwalPikets->first(function($jp) {
            return Carbon::parse($jp->tanggal_piket)->isToday();
        });

        $canAjukanIzinSakit = false;
        $activePiketId = null;
        $activePiketStatus = null;
        $activePiketKeterangan = null;

        if ($todayPiket) {
            $activePiketId = $todayPiket->id;
            $activePiketStatus = $todayPiket->status;
            $activePiketKeterangan = $todayPiket->keterangan;
            // Hanya bisa mengajukan jika belum pernah mengisi laporan kelas dan belum berstatus Izin/Sakit
            $canAjukanIzinSakit = !in_array($todayPiket->status, ['Izin', 'Sakit', 'Hadir']) && $todayPiket->rekapKehadirans->count() === 0;
        }

        // Update status Hadir jika sudah ada rekap kelas
        foreach ($jadwalPikets as $jp) {
            if ($jp->rekapKehadirans->count() > 0 && $jp->status !== 'Hadir') {
                $jp->update(['status' => 'Hadir']);
            }

            $hariPiket = Carbon::parse($jp->tanggal_piket)->locale('id')->isoFormat('dddd');

            // Rentang waktu piket secara keseluruhan (dari jam pertama sampai jam terakhir)
            $jp->piket_waktu_mulai = $jp->jamPelajarans->min('waktu_mulai');
            $jp->piket_waktu_selesai = $jp->jamPelajarans->max('waktu_selesai');

            // Hitung status dan jumlah kelas per jam pelajaran tanpa membuang jam pelajaran dari jadwal
            $jp->jamPelajarans->each(function($jam) use ($jp, $hariPiket) {
                $mapelIds = JadwalMapel::where('hari', $hariPiket)
                    ->whereHas('jamPelajarans', function($q) use ($jam) {
                        $q->where('jam_pelajarans.id', $jam->id);
                    })
                    ->whereHas('kelas.jurusan.titiks', function($q) use ($jp) {
                        $q->where('titiks.id', $jp->titik_id);
                    })
                    ->pluck('id')
                    ->toArray();

                $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $jp->id)
                    ->where('jam_pelajaran_id', $jam->id)
                    ->pluck('jadwal_mapel_id')
                    ->toArray();

                $totalClasses = count($mapelIds);
                $reportedCount = count(array_intersect($mapelIds, $reportedMapelIds));
                $unreportedCount = max(0, $totalClasses - $reportedCount);

                $jam->total_classes = $totalClasses;
                $jam->reported_classes = $reportedCount;
                $jam->unreported_classes = $unreportedCount;
                $jam->is_completed = ($totalClasses > 0 && $unreportedCount === 0);
                $jam->has_no_classes = ($totalClasses === 0);
            });
        }

        return Inertia::render('guru-piket/Index', [
            'jadwalPikets' => $jadwalPikets,
            'canAjukanIzinSakit' => $canAjukanIzinSakit,
            'activePiketId' => $activePiketId,
            'activePiketStatus' => $activePiketStatus,
            'activePiketKeterangan' => $activePiketKeterangan,
            'serverTime' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function create(Request $request, $id)
    {
        $jadwalPiket = JadwalPiket::with(['jamPelajarans', 'titik'])->findOrFail($id);
        
        if (in_array($jadwalPiket->status, ['Izin', 'Sakit'])) {
            return redirect()->route('pelaporan-piket.index')->with('error', "Jadwal piket ini telah berstatus {$jadwalPiket->status}. Anda tidak perlu mengisi laporan kelas.");
        }

        $jamPelajaranId = $request->query('jam_pelajaran_id');
        if (!$jamPelajaranId) {
            abort(400, 'Jam Pelajaran ID is required');
        }

        $currentJam = $jadwalPiket->jamPelajarans->where('id', $jamPelajaranId)->first();
        if (!$currentJam) {
            abort(403, 'Jam Pelajaran ini tidak ada di jadwal piket Anda.');
        }

        $now = Carbon::now();
        
        // Batas waktu piket dihitung secara keseluruhan berdasarkan jadwal piket
        $piketMulai = $jadwalPiket->jamPelajarans->min('waktu_mulai') ?? $currentJam->waktu_mulai;
        $piketSelesai = $jadwalPiket->jamPelajarans->max('waktu_selesai') ?? $currentJam->waktu_selesai;

        $mulaiStr = strlen($piketMulai) <= 5 ? $piketMulai . ':00' : $piketMulai;
        $selesaiStr = strlen($piketSelesai) <= 5 ? $piketSelesai . ':00' : $piketSelesai;
        
        $startTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $endTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $selesaiStr);

        // Check if report time has passed
        $isTerlambat = false;
        if ($now->greaterThan($endTime)) {
            $izin = IzinPiket::where('jadwal_piket_id', $jadwalPiket->id)
                ->where('jam_pelajaran_id', $jamPelajaranId)
                ->where('guru_piket_id', auth()->id())
                ->first();

            if (!$izin || $izin->status !== 'approved') {
                if (!$izin) {
                    IzinPiket::create([
                        'jadwal_piket_id' => $jadwalPiket->id,
                        'jam_pelajaran_id' => $jamPelajaranId,
                        'guru_piket_id' => auth()->id(),
                        'status' => 'pending',
                    ]);
                }
                return redirect()->route('pelaporan-piket.index')->with('error', 'Anda terlambat melakukan pelaporan. Permohonan izin telah dikirim. Silakan hubungi Wakasek untuk mendapatkan akses.');
            }
            $isTerlambat = true;
        } else if ($now->lessThan($startTime)) {
            // Hanya validasi jika waktu belum dimulai
            return redirect()->route('pelaporan-piket.index')->with('error', 'Waktu pengisian laporan untuk jadwal piket ini belum dimulai.');
        }

        // Ambil hari berdasarkan tanggal_piket
        $hariPiket = Carbon::parse($jadwalPiket->tanggal_piket)->locale('id')->isoFormat('dddd');

        // Cari jadwal mapel di hari tersebut, jam tersebut, dan titik tersebut
        $baseQuery = JadwalMapel::with(['kelas', 'mataPelajaran', 'guru'])
            ->where('hari', $hariPiket)
            ->whereHas('jamPelajarans', function($q) use ($jamPelajaranId) {
                $q->where('jam_pelajarans.id', $jamPelajaranId);
            })
            ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                $q->where('titiks.id', $jadwalPiket->titik_id);
            });

        $totalClasses = $baseQuery->count();
        $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $jadwalPiket->id)
            ->where('jam_pelajaran_id', $jamPelajaranId)
            ->pluck('jadwal_mapel_id');
        $jadwalMapels = $baseQuery->whereNotIn('id', $reportedMapelIds)->get();

        // Cek apakah sudah ada foto dokumentasi yang diambil sebelumnya untuk jadwal piket ini / hari ini
        $existingRekap = RekapKehadiran::where(function($query) use ($jadwalPiket) {
                $query->where('jadwal_piket_id', $jadwalPiket->id)
                      ->orWhereHas('jadwalPiket', function($q) use ($jadwalPiket) {
                          $q->where('guru_piket_id', $jadwalPiket->guru_piket_id)
                            ->whereDate('tanggal_piket', $jadwalPiket->tanggal_piket);
                      });
            })
            ->whereNotNull('foto_dokumentasi')
            ->where('foto_dokumentasi', '!=', '')
            ->with(['jadwalMapel.kelas', 'jadwalMapel.jamPelajarans'])
            ->oldest()
            ->first();

        $existingPhotoPath = ($existingRekap && Storage::disk('public')->exists($existingRekap->foto_dokumentasi))
            ? $existingRekap->foto_dokumentasi
            : null;

        $existingPhotoUrl = $existingPhotoPath ? asset('storage/' . $existingPhotoPath) : null;

        $existingPhotoNote = null;
        if ($existingRekap && $existingPhotoPath) {
            $jamList = $existingRekap->jadwalMapel?->jamPelajarans?->map(fn($j) => 'Jam ke-' . $j->urutan_jam)->join(', ');
            $kelasName = $existingRekap->jadwalMapel?->kelas?->nama_kelas;
            $timeFormatted = $existingRekap->created_at ? $existingRekap->created_at->format('H:i') : null;

            $details = array_filter([
                $kelasName ? "Kelas {$kelasName}" : null,
                $jamList ? "({$jamList})" : null,
                $timeFormatted ? "pukul {$timeFormatted} WIB" : null
            ]);

            $existingPhotoNote = !empty($details)
                ? 'Foto diambil saat pelaporan ' . implode(' ', $details)
                : 'Foto diambil dari pelaporan kelas sebelumnya';
        }

        return Inertia::render('guru-piket/Create', [
            'jadwalPiket' => $jadwalPiket,
            'jadwalMapels' => $jadwalMapels,
            'totalClasses' => $totalClasses,
            'currentJam' => $currentJam,
            'isTerlambat' => $isTerlambat,
            'existingPhotoUrl' => $existingPhotoUrl,
            'existingPhotoNote' => $existingPhotoNote,
            'piketWaktuMulai' => $piketMulai,
            'piketWaktuSelesai' => $piketSelesai,
        ]);
    }

    public function store(Request $request)
    {
        $jadwalPiket = JadwalPiket::with('jamPelajarans')->findOrFail($request->jadwal_piket_id);

        if ($jadwalPiket->guru_piket_id !== Auth::id()) {
            abort(403);
        }

        $currentJam = $jadwalPiket->jamPelajarans->where('id', $request->jam_pelajaran_id)->first();
        if (!$currentJam) {
            abort(403, 'Jam Pelajaran ini tidak ada di jadwal piket Anda.');
        }

        $currentTime = Carbon::now();

        // Batas waktu piket dihitung secara keseluruhan berdasarkan jadwal piket
        $piketMulai = $jadwalPiket->jamPelajarans->min('waktu_mulai') ?? $currentJam->waktu_mulai;
        $piketSelesai = $jadwalPiket->jamPelajarans->max('waktu_selesai') ?? $currentJam->waktu_selesai;

        $mulaiStr = strlen($piketMulai) <= 5 ? $piketMulai . ':00' : $piketMulai;
        $selesaiStr = strlen($piketSelesai) <= 5 ? $piketSelesai . ':00' : $piketSelesai;

        $waktuMulai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $waktuSelesai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $selesaiStr);

        $isTerlambat = false;
        $izin = null;
        if ($currentTime->greaterThan($waktuSelesai)) {
            $izin = IzinPiket::where('jadwal_piket_id', $request->jadwal_piket_id)
                ->where('jam_pelajaran_id', $request->jam_pelajaran_id)
                ->where('guru_piket_id', auth()->id())
                ->first();

            if (!$izin || $izin->status !== 'approved') {
                return back()->withErrors(['error' => 'Waktu pelaporan di luar batas dan izin belum disetujui.']);
            }
            $isTerlambat = true;
        } else if ($currentTime->lessThan($waktuMulai)) {
             return back()->withErrors(['error' => 'Waktu pelaporan jadwal piket belum dimulai.']);
        }

        // Cari foto dokumentasi yang sudah ada untuk jadwal piket ini / hari ini
        $existingRekap = RekapKehadiran::where(function($query) use ($jadwalPiket) {
                $query->where('jadwal_piket_id', $jadwalPiket->id)
                      ->orWhereHas('jadwalPiket', function($q) use ($jadwalPiket) {
                          $q->where('guru_piket_id', $jadwalPiket->guru_piket_id)
                            ->whereDate('tanggal_piket', $jadwalPiket->tanggal_piket);
                      });
            })
            ->whereNotNull('foto_dokumentasi')
            ->where('foto_dokumentasi', '!=', '')
            ->oldest()
            ->first();

        $existingFoto = ($existingRekap && Storage::disk('public')->exists($existingRekap->foto_dokumentasi))
            ? $existingRekap->foto_dokumentasi
            : null;

        $isBatch = $request->has('reports') && is_array($request->reports) && count($request->reports) > 0;

        if ($isBatch) {
            $rules = [
                'jadwal_piket_id' => 'required|exists:jadwal_pikets,id',
                'jam_pelajaran_id' => 'required|exists:jam_pelajarans,id',
                'reports' => 'required|array|min:1',
                'reports.*.jadwal_mapel_id' => 'required|exists:jadwal_mapels,id',
                'reports.*.status_kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpha',
                'reports.*.keterangan' => 'nullable|string|max:500',
                'foto_dokumentasi' => ($isTerlambat || $existingFoto) ? 'nullable|image|max:5120' : 'required|image|max:5120',
            ];

            $messages = [
                'reports.required' => 'Daftar laporan kelas wajib diisi.',
                'reports.*.status_kehadiran.required' => 'Status kehadiran untuk setiap kelas wajib dipilih.',
                'foto_dokumentasi.required' => 'Foto dokumentasi wajib diisi untuk pelaporan tepat waktu.'
            ];

            $request->validate($rules, $messages);

            $path = null;
            if ($existingFoto) {
                $path = $existingFoto;
            } elseif ($request->hasFile('foto_dokumentasi')) {
                $path = ImageCompressor::storeCompressed($request->file('foto_dokumentasi'), 'pelaporan', 100);
            }

            \Illuminate\Support\Facades\DB::transaction(function() use ($request, $jadwalPiket, $path, $isTerlambat) {
                foreach ($request->reports as $reportItem) {
                    $alreadyReported = RekapKehadiran::where('jadwal_piket_id', $jadwalPiket->id)
                        ->where('jam_pelajaran_id', $request->jam_pelajaran_id)
                        ->where('jadwal_mapel_id', $reportItem['jadwal_mapel_id'])
                        ->first();

                    $ket = !empty($reportItem['keterangan']) ? trim($reportItem['keterangan']) : null;

                    if (!$alreadyReported) {
                        RekapKehadiran::create([
                            'jadwal_mapel_id' => $reportItem['jadwal_mapel_id'],
                            'jadwal_piket_id' => $jadwalPiket->id,
                            'jam_pelajaran_id' => $request->jam_pelajaran_id,
                            'status_kehadiran' => $reportItem['status_kehadiran'],
                            'foto_dokumentasi' => $path,
                            'keterangan' => $ket,
                            'is_terlambat' => $isTerlambat,
                        ]);
                    } else {
                        $alreadyReported->update([
                            'jam_pelajaran_id' => $request->jam_pelajaran_id ?? $alreadyReported->jam_pelajaran_id,
                            'status_kehadiran' => $reportItem['status_kehadiran'],
                            'foto_dokumentasi' => $path ?? $alreadyReported->foto_dokumentasi,
                            'keterangan' => $ket,
                            'is_terlambat' => $isTerlambat,
                        ]);
                    }
                }

                if ($jadwalPiket->status !== 'Hadir') {
                    $jadwalPiket->update(['status' => 'Hadir']);
                }
            });
        } else {
            $rules = [
                'jadwal_piket_id' => 'required|exists:jadwal_pikets,id',
                'jam_pelajaran_id' => 'required|exists:jam_pelajarans,id',
                'jadwal_mapel_id' => [
                    'required',
                    'exists:jadwal_mapels,id',
                    Rule::unique('rekap_kehadirans')->where(function ($query) use ($request) {
                        return $query->where('jadwal_piket_id', $request->jadwal_piket_id)
                                     ->where('jam_pelajaran_id', $request->jam_pelajaran_id);
                    })
                ],
                'status_kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpha',
                'foto_dokumentasi' => ($isTerlambat || $existingFoto) ? 'nullable|image|max:5120' : 'required|image|max:5120',
                'keterangan' => 'nullable|string',
            ];

            $messages = [
                'jadwal_mapel_id.unique' => 'Kelas ini sudah dilaporkan pada shift ini.',
                'foto_dokumentasi.required' => 'Foto dokumentasi wajib diisi untuk pelaporan tepat waktu.'
            ];

            $request->validate($rules, $messages);

            $path = null;
            if ($existingFoto) {
                $path = $existingFoto;
            } elseif ($request->hasFile('foto_dokumentasi')) {
                $path = ImageCompressor::storeCompressed($request->file('foto_dokumentasi'), 'pelaporan', 100);
            }

            RekapKehadiran::create([
                'jadwal_mapel_id' => $request->jadwal_mapel_id,
                'jadwal_piket_id' => $request->jadwal_piket_id,
                'jam_pelajaran_id' => $request->jam_pelajaran_id,
                'status_kehadiran' => $request->status_kehadiran,
                'foto_dokumentasi' => $path,
                'keterangan' => $request->keterangan,
                'is_terlambat' => $isTerlambat,
            ]);

            if ($jadwalPiket->status !== 'Hadir') {
                $jadwalPiket->update(['status' => 'Hadir']);
            }
        }

        $hariPiket = Carbon::parse($jadwalPiket->tanggal_piket)->locale('id')->isoFormat('dddd');
        $baseQuery = JadwalMapel::where('hari', $hariPiket)
            ->whereHas('jamPelajarans', function($q) use ($request) {
                $q->where('jam_pelajarans.id', $request->jam_pelajaran_id);
            })
            ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                $q->where('titiks.id', $jadwalPiket->titik_id);
            });
        
        $reportedMapelIds = RekapKehadiran::where('jadwal_piket_id', $request->jadwal_piket_id)
            ->where('jam_pelajaran_id', $request->jam_pelajaran_id)
            ->pluck('jadwal_mapel_id');
        $unreportedCount = $baseQuery->whereNotIn('id', $reportedMapelIds)->count();

        if ($unreportedCount == 0) {
            if ($isTerlambat && $izin) {
                $izin->update(['status' => 'completed']);
            }
            return redirect()->route('pelaporan-piket.index')->with('success', 'Laporan kehadiran berhasil disimpan. Semua kelas untuk jam ini telah selesai dilaporkan.');
        } else {
            return redirect()->route('pelaporan-piket.index')
                ->with('success', 'Laporan kehadiran berhasil disimpan. Masih tersisa ' . $unreportedCount . ' kelas lainnya pada jam ini.');
        }
    }

    public function ajukanIzinSakit(Request $request, $id)
    {
        $jadwalPiket = JadwalPiket::findOrFail($id);

        if ($jadwalPiket->guru_piket_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak akses pada jadwal piket ini.');
        }

        if ($jadwalPiket->rekapKehadirans()->count() > 0) {
            return back()->with('error', 'Tidak dapat mengajukan Izin/Sakit karena Anda sudah mengisi laporan pemantauan kelas pada jadwal ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Izin,Sakit',
            'keterangan' => 'required|string|max:500',
        ], [
            'status.required' => 'Pilih jenis status (Izin atau Sakit).',
            'status.in' => 'Status harus berupa Izin atau Sakit.',
            'keterangan.required' => 'Keterangan/alasan wajib diisi.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
        ]);

        $jadwalPiket->update([
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'],
        ]);

        return redirect()->route('pelaporan-piket.index')->with('success', "Permohonan {$validated['status']} tugas piket berhasil disimpan.");
    }
}
