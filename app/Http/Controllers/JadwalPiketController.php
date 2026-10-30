<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\Titik;
use App\Models\User;
use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JadwalPiketController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('id');

        $dates = $this->getPeriodeDates($request);
        $query = JadwalPiket::with(['titik', 'guruPiket', 'jamPelajarans']);

        if ($dates) {
            $query->whereDate('tanggal_piket', '>=', $dates[0])
                  ->whereDate('tanggal_piket', '<=', $dates[1]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->whereHas('guruPiket', function($gq) use ($search) {
                    $gq->where('name', 'like', "%{$search}%");
                })->orWhereHas('titik', function($tq) use ($search) {
                    $tq->where('nama_titik', 'like', "%{$search}%");
                });
            });
        }

        // Urutkan ASC berdasarkan tanggal agar jadwal Senin-Jumat berurutan rapi dalam 1 minggu
        $jadwalPikets = $query->orderBy('tanggal_piket', 'asc')
                              ->orderBy('titik_id', 'asc')
                              ->paginate(15)
                              ->withQueryString();

        $activePeriodInfo = null;
        if ($dates) {
            $activePeriodInfo = [
                'start_date' => $dates[0]->toDateString(),
                'end_date' => $dates[1]->toDateString(),
                'start_formatted' => $dates[0]->translatedFormat('l, d F Y'),
                'end_formatted' => $dates[1]->translatedFormat('l, d F Y'),
                'range_label' => $dates[0]->translatedFormat('d M Y') . ' - ' . $dates[1]->translatedFormat('d M Y'),
                'short_label' => $dates[0]->translatedFormat('d M') . ' s/d ' . $dates[1]->translatedFormat('d M Y'),
            ];
        }

        $now = Carbon::now();
        $currentWeekInfo = [
            'tahun' => (int) $now->year,
            'bulan' => (int) $now->month,
            'minggu' => (int) $this->calculateWeekOfMonth($now),
        ];

        return Inertia::render('jadwal-piket/Index', [
            'jadwalPikets' => $jadwalPikets,
            'filters' => $this->getFilters($request),
            'available_years' => $this->getAvailableYears(),
            'activePeriodInfo' => $activePeriodInfo,
            'currentWeekInfo' => $currentWeekInfo,
            'totalPiketAll' => JadwalPiket::count(),
        ]);
    }

    public function create()
    {
        return Inertia::render('jadwal-piket/Create', [
            'titiks' => Titik::orderBy('nama_titik')->get(),
            'gurus' => User::where('role', 'guru')->whereHas('tipeGuru', function($q) {
                $q->where('akses_piket_kelas', true);
            })->orderBy('name')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateJadwalPiket($request);

        $jadwalPiket = JadwalPiket::create([
            'tanggal_piket' => $validated['tanggal_piket'],
            'titik_id' => $validated['titik_id'],
            'guru_piket_id' => $validated['guru_piket_id']
        ]);

        $jadwalPiket->jamPelajarans()->sync($validated['jam_pelajaran_ids']);

        $createdDate = Carbon::parse($validated['tanggal_piket']);
        $week = $this->calculateWeekOfMonth($createdDate);

        return redirect()->route('jadwal-piket.index', [
            'filter_type' => 'minggu',
            'filter_tahun' => $createdDate->year,
            'filter_bulan' => $createdDate->month,
            'filter_minggu' => $week,
        ])->with('success', 'Jadwal Piket berhasil ditambahkan.');
    }

    public function edit(JadwalPiket $jadwalPiket)
    {
        $jadwalPiket->load('jamPelajarans');
        
        return Inertia::render('jadwal-piket/Edit', [
            'jadwalPiket' => $jadwalPiket,
            'titiks' => Titik::orderBy('nama_titik')->get(),
            'gurus' => User::where('role', 'guru')->whereHas('tipeGuru', function($q) {
                $q->where('akses_piket_kelas', true);
            })->orderBy('name')->get(),
            'jamPelajarans' => JamPelajaran::orderBy('urutan_jam')->get()
        ]);
    }

    public function update(Request $request, JadwalPiket $jadwalPiket)
    {
        $validated = $this->validateJadwalPiket($request, $jadwalPiket->id);

        $jadwalPiket->update([
            'tanggal_piket' => $validated['tanggal_piket'],
            'titik_id' => $validated['titik_id'],
            'guru_piket_id' => $validated['guru_piket_id']
        ]);

        $jadwalPiket->jamPelajarans()->sync($validated['jam_pelajaran_ids']);

        $updatedDate = Carbon::parse($validated['tanggal_piket']);
        $week = $this->calculateWeekOfMonth($updatedDate);

        return redirect()->route('jadwal-piket.index', [
            'filter_type' => 'minggu',
            'filter_tahun' => $updatedDate->year,
            'filter_bulan' => $updatedDate->month,
            'filter_minggu' => $week,
        ])->with('success', 'Jadwal Piket berhasil diperbarui.');
    }

    protected function validateJadwalPiket(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'tanggal_piket' => 'required|date',
            'titik_id' => 'required|exists:titiks,id',
            'guru_piket_id' => 'required|exists:users,id',
            'jam_pelajaran_ids' => 'required|array|min:1',
            'jam_pelajaran_ids.*' => 'exists:jam_pelajarans,id'
        ], [
            'jam_pelajaran_ids.required' => 'Pilih minimal satu jam pelajaran.',
            'jam_pelajaran_ids.min' => 'Pilih minimal satu jam pelajaran.',
        ]);

        $titikConflict = JadwalPiket::where('tanggal_piket', $validated['tanggal_piket'])
            ->where('titik_id', $validated['titik_id'])
            ->when($ignoreId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->whereHas('jamPelajarans', function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids']);
            })
            ->with(['guruPiket', 'titik', 'jamPelajarans' => function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids'])->orderBy('urutan_jam');
            }])
            ->first();

        if ($titikConflict) {
            $jamNames = $titikConflict->jamPelajarans->map(fn($j) => 'Jam ke-' . $j->urutan_jam)->join(', ');
            $isSameGuru = $titikConflict->guru_piket_id == $validated['guru_piket_id'];

            $errorMessage = $isSameGuru
                ? "Guru tersebut sudah memiliki jadwal piket di titik ini pada {$jamNames} untuk tanggal tersebut."
                : "Titik lokasi ini sudah dijadwalkan untuk {$titikConflict->guruPiket?->name} pada {$jamNames}. Jadwal piket pada titik dan jam yang sama tidak boleh bertabrakan.";

            throw \Illuminate\Validation\ValidationException::withMessages([
                'titik_id' => [$errorMessage],
                'jam_pelajaran_ids' => ["Bentrok jam pelajaran pada {$jamNames}."],
            ]);
        }

        $guruConflict = JadwalPiket::where('tanggal_piket', $validated['tanggal_piket'])
            ->where('guru_piket_id', $validated['guru_piket_id'])
            ->when($ignoreId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->whereHas('jamPelajarans', function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids']);
            })
            ->with(['titik', 'guruPiket', 'jamPelajarans' => function ($query) use ($validated) {
                $query->whereIn('jam_pelajarans.id', $validated['jam_pelajaran_ids'])->orderBy('urutan_jam');
            }])
            ->first();

        if ($guruConflict) {
            $jamNames = $guruConflict->jamPelajarans->map(fn($j) => 'Jam ke-' . $j->urutan_jam)->join(', ');

            $errorMessage = "Guru ini sudah memiliki jadwal piket di {$guruConflict->titik?->nama_titik} pada {$jamNames} untuk tanggal tersebut.";

            throw \Illuminate\Validation\ValidationException::withMessages([
                'guru_piket_id' => [$errorMessage],
                'jam_pelajaran_ids' => ["Bentrok jam pelajaran pada {$jamNames}."],
            ]);
        }

        return $validated;
    }

    public function destroy(JadwalPiket $jadwalPiket)
    {
        foreach ($jadwalPiket->rekapKehadirans as $rekap) {
            if ($rekap->foto_dokumentasi && Storage::disk('public')->exists($rekap->foto_dokumentasi)) {
                Storage::disk('public')->delete($rekap->foto_dokumentasi);
            }
        }

        $jadwalPiket->delete();
        return redirect()->route('jadwal-piket.index')->with('success', 'Jadwal piket berhasil dihapus.');
    }

    private function getPeriodeDates(Request $request): ?array
    {
        $filterType = $request->input('filter_type', 'minggu');
        $now = Carbon::now();

        if ($filterType === 'semua') {
            return null;
        }

        if ($filterType === 'minggu') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);
            $minggu = (int) $request->input('filter_minggu', $this->calculateWeekOfMonth($now));

            $firstDayOfMonth = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $startOfWeek1 = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);
            
            $startOfTargetWeek = $startOfWeek1->copy()->addWeeks($minggu - 1)->startOfDay();
            $endOfTargetWeek = $startOfTargetWeek->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            return [
                $startOfTargetWeek,
                $endOfTargetWeek,
            ];
        }

        if ($filterType === 'bulan') {
            $tahun = (int) $request->input('filter_tahun', $now->year);
            $bulan = (int) $request->input('filter_bulan', $now->month);

            return [
                Carbon::create($tahun, $bulan, 1)->startOfDay(),
                Carbon::create($tahun, $bulan, 1)->endOfMonth()->endOfDay(),
            ];
        }

        if ($filterType === 'semester') {
            $year = (int) $request->input('filter_tahun', $now->year);
            $semester = $request->input('filter_semester', $now->month >= 7 ? 'Ganjil' : 'Genap');

            if ($semester === 'Ganjil') {
                return [
                    Carbon::create($year, 7, 1)->startOfDay(),
                    Carbon::create($year, 12, 31)->endOfDay(),
                ];
            }

            return [
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 6, 30)->endOfDay(),
            ];
        }

        if ($filterType === 'tahun') {
            $year = (int) $request->input('filter_tahun', $now->year);

            return [
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 12, 31)->endOfDay(),
            ];
        }

        if ($filterType === 'kustom') {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                return [
                    Carbon::parse($request->input('start_date'))->startOfDay(),
                    Carbon::parse($request->input('end_date'))->endOfDay(),
                ];
            }
        }

        return null;
    }

    private function getFilters(Request $request): array
    {
        $now = Carbon::now();
        return [
            'filter_type' => $request->input('filter_type', 'minggu'),
            'filter_tahun' => (int) $request->input('filter_tahun', $now->year),
            'filter_bulan' => (int) $request->input('filter_bulan', $now->month),
            'filter_minggu' => (int) $request->input('filter_minggu', $this->calculateWeekOfMonth($now)),
            'filter_semester' => $request->input('filter_semester', $now->month >= 7 ? 'Ganjil' : 'Genap'),
            'search' => $request->input('search', ''),
            'start_date' => $request->input('start_date', ''),
            'end_date' => $request->input('end_date', ''),
        ];
    }

    private function calculateWeekOfMonth(Carbon $date): int
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $startOfFirstWeek = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $startOfCurrentWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        
        return (int) ($startOfFirstWeek->diffInWeeks($startOfCurrentWeek) + 1);
    }

    private function getAvailableYears(): array
    {
        $currentYear = (int) Carbon::now()->format('Y');
        $minPiketDate = JadwalPiket::min('tanggal_piket');
        $startYear = $minPiketDate ? (int) Carbon::parse($minPiketDate)->format('Y') : $currentYear;
        $maxYear = $currentYear + 1;

        return range(min($startYear, $currentYear), $maxYear);
    }
}
