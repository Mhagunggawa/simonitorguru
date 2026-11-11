<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\JadwalMapel;
use App\Models\RekapKehadiran;
use App\Models\PengaturanPiket;
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
        $pengaturan = PengaturanPiket::getPengaturan();
        $cutoffTimeStr = $pengaturan->waktu_selesai ?: '16:00:00';
        if (strlen($cutoffTimeStr) <= 5) $cutoffTimeStr .= ':00';

        $userPiketIdsWithIzin = IzinPiket::where('guru_piket_id', auth()->id())->pluck('jadwal_piket_id');

        $jadwalPikets = JadwalPiket::with(['jamPelajarans', 'titik', 'rekapKehadirans'])
            ->where('guru_piket_id', auth()->id())
            ->where(function($q) use ($userPiketIdsWithIzin) {
                $q->whereDate('tanggal_piket', today())
                  ->orWhereIn('id', $userPiketIdsWithIzin)
                  ->orWhere(function($sub) {
                      // Jadwal dalam 7 hari terakhir yang belum tuntas dilaporkan
                      $sub->whereDate('tanggal_piket', '>=', now()->subDays(7))
                          ->whereDate('tanggal_piket', '<=', today())
                          ->where(function($s) {
                              $s->whereNull('status')
                                ->orWhereNotIn('status', ['Hadir', 'Izin', 'Sakit']);
                          });
                  });
            })
            ->orderBy('tanggal_piket', 'desc')
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

            // Rentang waktu piket secara keseluruhan
            $jp->piket_waktu_mulai = $jp->jamPelajarans->min('waktu_mulai');
            $jp->piket_waktu_selesai = substr($cutoffTimeStr, 0, 5);

            // Cek apakah jadwal ini sudah melewati batas waktu pelaporan
            $deadline = Carbon::parse($jp->tanggal_piket . ' ' . $cutoffTimeStr);
            $jp->is_expired = now()->greaterThan($deadline);
            $jp->batas_waktu_pelaporan = substr($cutoffTimeStr, 0, 5);

            // Cek permohonan izin susulan
            $izins = IzinPiket::where('jadwal_piket_id', $jp->id)
                ->where('guru_piket_id', auth()->id())
                ->get();
            $jp->has_approved_izin = $izins->where('status', 'approved')->isNotEmpty();
            $jp->has_pending_izin = $izins->where('status', 'pending')->isNotEmpty();

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
            'pengaturan' => [
                'waktu_mulai' => $pengaturan->waktu_mulai ? substr($pengaturan->waktu_mulai, 0, 5) : '06:30',
                'waktu_selesai' => $pengaturan->waktu_selesai ? substr($pengaturan->waktu_selesai, 0, 5) : '16:00',
                'keterangan' => $pengaturan->keterangan,
            ],
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
        
        $pengaturan = PengaturanPiket::getPengaturan();
        $cutoffTimeStr = $pengaturan->waktu_selesai ?: '16:00:00';
        if (strlen($cutoffTimeStr) <= 5) $cutoffTimeStr .= ':00';

        // Jam mulai mengikuti jam pelajaran spesifik ini
        $jamMulai = $currentJam->waktu_mulai;
        $mulaiStr = strlen($jamMulai) <= 5 ? $jamMulai . ':00' : $jamMulai;
        
        $startTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $endTime = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $cutoffTimeStr);

        $isBelumMulai = $now->lessThan($startTime);

        // Check if report time has passed
        $isTerlambat = false;
        if ($now->greaterThan($endTime)) {
            $izin = IzinPiket::where('jadwal_piket_id', $jadwalPiket->id)
                ->where('guru_piket_id', auth()->id())
                ->where('status', 'approved')
                ->first();

            if (!$izin) {
                IzinPiket::firstOrCreate([
                    'jadwal_piket_id' => $jadwalPiket->id,
                    'jam_pelajaran_id' => $jamPelajaranId,
                    'guru_piket_id' => auth()->id(),
                ], [
                    'status' => 'pending',
                ]);
                return redirect()->route('pelaporan-piket.index')->with('error', 'Waktu pengisian laporan telah melewati batas waktu (' . substr($cutoffTimeStr, 0, 5) . ' WIB). Permohonan izin susulan telah dikirim ke Wakasek.');
            }
            $isTerlambat = true;
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
            'isBelumMulai' => $isBelumMulai,
            'jamMulaiStr' => substr($mulaiStr, 0, 5) . ' WIB',
            'existingPhotoUrl' => $existingPhotoUrl,
            'existingPhotoNote' => $existingPhotoNote,
            'piketWaktuMulai' => substr($mulaiStr, 0, 5),
            'piketWaktuSelesai' => substr($cutoffTimeStr, 0, 5),
            'deadline' => $endTime->toIso8601String(),
            'batasWaktuStr' => substr($cutoffTimeStr, 0, 5) . ' WIB',
            'serverTime' => Carbon::now()->toIso8601String(),
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

        $pengaturan = PengaturanPiket::getPengaturan();
        $cutoffTimeStr = $pengaturan->waktu_selesai ?: '16:00:00';
        if (strlen($cutoffTimeStr) <= 5) $cutoffTimeStr .= ':00';

        $jamMulai = $currentJam->waktu_mulai;
        $mulaiStr = strlen($jamMulai) <= 5 ? $jamMulai . ':00' : $jamMulai;

        $waktuMulai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $mulaiStr);
        $waktuSelesai = Carbon::parse($jadwalPiket->tanggal_piket . ' ' . $cutoffTimeStr);

        $isTerlambat = false;
        $izin = null;
        if ($currentTime->greaterThan($waktuSelesai)) {
            $izin = IzinPiket::where('jadwal_piket_id', $request->jadwal_piket_id)
                ->where('guru_piket_id', auth()->id())
                ->where('status', 'approved')
                ->first();

            if (!$izin) {
                return back()->withErrors(['error' => 'Waktu pelaporan di luar batas (' . substr($cutoffTimeStr, 0, 5) . ' WIB) dan izin belum disetujui.']);
            }
            $isTerlambat = true;
        } else if ($currentTime->lessThan($waktuMulai)) {
             return back()->withErrors(['error' => 'Jam pelajaran ke-' . $currentJam->urutan_jam . ' belum dimulai (dimulai pukul ' . substr($mulaiStr, 0, 5) . ' WIB).']);
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

        // Cek apakah seluruh jam pelajaran pada sesi piket ini sudah tuntas dilaporkan
        $allSessionCompleted = true;
        foreach ($jadwalPiket->jamPelajarans as $jam) {
            $baseJamQuery = JadwalMapel::where('hari', $hariPiket)
                ->whereHas('jamPelajarans', function($q) use ($jam) {
                    $q->where('jam_pelajarans.id', $jam->id);
                })
                ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                    $q->where('titiks.id', $jadwalPiket->titik_id);
                });
            
            $reportedJamIds = RekapKehadiran::where('jadwal_piket_id', $jadwalPiket->id)
                ->where('jam_pelajaran_id', $jam->id)
                ->pluck('jadwal_mapel_id');
            
            if ($baseJamQuery->whereNotIn('id', $reportedJamIds)->count() > 0) {
                $allSessionCompleted = false;
                break;
            }
        }

        if ($allSessionCompleted) {
            if ($isTerlambat) {
                IzinPiket::where('jadwal_piket_id', $jadwalPiket->id)
                    ->where('guru_piket_id', auth()->id())
                    ->update(['status' => 'completed']);
            }
            return redirect()->route('pelaporan-piket.index')->with('success', 'Laporan kehadiran berhasil disimpan. Seluruh tugas piket Anda untuk hari ini telah selesai!');
        } else {
            return redirect()->route('pelaporan-piket.index')
                ->with('success', 'Laporan kehadiran jam ini berhasil disimpan. Silakan lanjutkan pengisian jam pelajaran berikutnya.');
        }
    }

    public function mintaIzinSusulan(Request $request, $id)
    {
        $jadwalPiket = JadwalPiket::with('jamPelajarans')->findOrFail($id);

        if ($jadwalPiket->guru_piket_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak akses pada jadwal piket ini.');
        }

        $hariPiket = Carbon::parse($jadwalPiket->tanggal_piket)->locale('id')->isoFormat('dddd');

        foreach ($jadwalPiket->jamPelajarans as $jam) {
            $baseQuery = JadwalMapel::where('hari', $hariPiket)
                ->whereHas('jamPelajarans', function($q) use ($jam) {
                    $q->where('jam_pelajarans.id', $jam->id);
                })
                ->whereHas('kelas.jurusan.titiks', function($q) use ($jadwalPiket) {
                    $q->where('titiks.id', $jadwalPiket->titik_id);
                });

            $reportedIds = RekapKehadiran::where('jadwal_piket_id', $jadwalPiket->id)
                ->where('jam_pelajaran_id', $jam->id)
                ->pluck('jadwal_mapel_id');

            if ($baseQuery->whereNotIn('id', $reportedIds)->count() > 0) {
                IzinPiket::firstOrCreate([
                    'jadwal_piket_id' => $jadwalPiket->id,
                    'jam_pelajaran_id' => $jam->id,
                    'guru_piket_id' => auth()->id(),
                ], [
                    'status' => 'pending'
                ]);
            }
        }

        return redirect()->route('pelaporan-piket.index')->with('success', 'Permohonan izin susulan berhasil diajukan ke Wakasek.');
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
