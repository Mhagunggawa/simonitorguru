<?php

namespace App\Http\Controllers;

use App\Models\AbsensiUpacara;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AbsensiUpacaraReportController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('id');

        // Filter per bulan: format YYYY-MM, default ke bulan saat ini
        $defaultPeriod = Carbon::now()->format('Y-m');
        $period = $request->query('period', $defaultPeriod);

        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
        } else {
            $year = (int)Carbon::now()->format('Y');
            $month = (int)Carbon::now()->format('m');
            $period = $defaultPeriod;
        }

        // Ambil data absensi upacara khusus pada bulan dan tahun yang dipilih
        $attendancesData = AbsensiUpacara::with('user')
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->orderBy('tanggal', 'desc')
            ->get();

        $groupedByDate = $attendancesData->groupBy(function($item) {
            return Carbon::parse($item->tanggal)->format('Y-m-d');
        });

        $formattedReports = [];
        $totalMonthlyHadir = 0;
        $totalMonthlyTidakHadir = 0;
        $totalMonthlyAlpha = 0;
        $totalMonthlySakit = 0;
        $totalMonthlyIzin = 0;

        foreach ($groupedByDate as $date => $attendances) {
            $hadirList = $attendances->filter(function($a) {
                return $a->is_hadir || $a->status === 'Hadir';
            })->map(function($a) {
                return [
                    'id' => $a->user->id ?? 0,
                    'name' => $a->user->name ?? 'Unknown',
                    'status' => 'Hadir',
                ];
            })->values();

            $tidakHadirList = $attendances->filter(function($a) {
                return !$a->is_hadir && in_array($a->status, ['Alpha', 'Sakit', 'Izin']);
            })->map(function($a) {
                return [
                    'id' => $a->user->id ?? 0,
                    'name' => $a->user->name ?? 'Unknown',
                    'status' => $a->status ?? 'Alpha',
                    'keterangan' => $a->keterangan ?? '',
                ];
            })->values();

            $hadirCount = $hadirList->count();
            $tidakHadirCount = $tidakHadirList->count();
            $alphaCount = $tidakHadirList->where('status', 'Alpha')->count();
            $sakitCount = $tidakHadirList->where('status', 'Sakit')->count();
            $izinCount = $tidakHadirList->where('status', 'Izin')->count();

            $totalMonthlyHadir += $hadirCount;
            $totalMonthlyTidakHadir += $tidakHadirCount;
            $totalMonthlyAlpha += $alphaCount;
            $totalMonthlySakit += $sakitCount;
            $totalMonthlyIzin += $izinCount;

            $formattedReports[] = [
                'tanggal' => $date,
                'tanggal_format' => Carbon::parse($date)->translatedFormat('l, d F Y'),
                'hadir' => $hadirList,
                'tidak_hadir' => $tidakHadirList,
                'total_hadir' => $hadirCount,
                'total_tidak_hadir' => $tidakHadirCount,
                'total_alpha' => $alphaCount,
                'total_sakit' => $sakitCount,
                'total_izin' => $izinCount,
            ];
        }

        $periodDate = Carbon::createFromDate($year, $month, 1);

        return Inertia::render('LaporanAbsensiUpacara/Index', [
            'reports' => $formattedReports,
            'filter' => [
                'period' => $period,
                'period_label' => $periodDate->translatedFormat('F Y'),
                'month' => $month,
                'year' => $year,
            ],
            'summary' => [
                'total_upacara' => count($formattedReports),
                'total_hadir' => $totalMonthlyHadir,
                'total_tidak_hadir' => $totalMonthlyTidakHadir,
                'total_alpha' => $totalMonthlyAlpha,
                'total_sakit' => $totalMonthlySakit,
                'total_izin' => $totalMonthlyIzin,
            ]
        ]);
    }
}
