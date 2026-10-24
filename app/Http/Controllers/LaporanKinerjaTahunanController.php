<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Kinerja;
use Carbon\Carbon;

class LaporanKinerjaTahunanController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('id');

        $currentYear = (int) date('Y');

        // Mengambil seluruh tahun unik yang pernah tercatat di tabel kinerjas
        $dbYears = Kinerja::selectRaw("DISTINCT CAST(SUBSTRING_INDEX(periode, ' ', -1) AS UNSIGNED) as yr")
            ->pluck('yr')
            ->filter(fn($y) => $y >= 2020)
            ->toArray();

        // Daftar tahun tersedia dimulai dari tahun sekarang dan tahun-tahun sebelumnya
        $defaultYears = range($currentYear, max($currentYear - 4, 2024));
        $availableYears = array_values(array_unique(array_merge([$currentYear], $dbYears, $defaultYears)));
        rsort($availableYears);

        // Filter strictly per tahun (default: tahun berjalan)
        $selectedYear = (int) $request->query('year', $currentYear);
        $periode = 'Januari-Desember ' . $selectedYear;

        // Ambil seluruh guru yang menjadi objek penilaian kinerja
        $users = User::where(function ($q) {
                $q->whereIn('role', ['guru', 'wakasek'])
                  ->orWhereNotNull('observer_id');
            })
            ->with([
                'observer:id,name,role',
                'kinerjas' => function ($q) use ($periode) {
                    $q->where('periode', $periode)
                      ->with([
                          'fokuses',
                          'observer:id,name,role',
                          'approver:id,name',
                          'rejecter:id,name'
                      ]);
                }
            ])
            ->orderBy('name')
            ->get();

        // Transform data guru dan hasil pengisian tiap tahap
        $data = $users->map(function ($user) {
            /** @var Kinerja|null $kinerja */
            $kinerja = $user->kinerjas->first();
            $observer = $kinerja?->observer ?? $user->observer;

            // --- Tahap 1 Data ---
            $tahap1Exists = $kinerja !== null;
            $statusTahap1 = $kinerja?->status_tahap1 ?? 'belum_mulai';

            $fokusesData = [];
            if ($kinerja && $kinerja->fokuses) {
                $fokusesData = $kinerja->fokuses->map(function ($f) {
                    $rating = $f->penilaian ?? ($f->penilaians[0] ?? null);
                    $ratingLabel = match ((int)$rating) {
                        1 => 'Belum Dilakukan',
                        2 => 'Dilakukan tapi Belum Efektif',
                        3 => 'Dilakukan dan Efektif',
                        default => 'Belum Dinilai'
                    };

                    return [
                        'id' => $f->id,
                        'nama_fokus' => $f->nama_fokus,
                        'perilaku_dianjurkan' => $f->perilaku_dianjurkan ?? [],
                        'perilaku_dihindari' => $f->perilaku_dihindari ?? [],
                        'penilaian' => $rating,
                        'penilaian_label' => $ratingLabel,
                        'catatan' => $f->catatan,
                    ];
                })->values()->toArray();
            }

            $tahap1 = [
                'has_data' => $tahap1Exists,
                'status' => $statusTahap1,
                'status_label' => $this->getStatusTahap1Label($statusTahap1),
                'tempat' => $kinerja?->tempat ?? '-',
                'tanggal' => $kinerja?->tanggal ? Carbon::parse($kinerja->tanggal)->translatedFormat('l, d F Y') : '-',
                'waktu' => $kinerja?->waktu ? substr($kinerja->waktu, 0, 5) : '-',
                'video_link' => $kinerja?->video_link,
                'rekomendasi' => $kinerja?->rekomendasi,
                'fokuses' => $fokusesData,
                'approved_by' => $kinerja?->approver?->name,
                'approved_at' => $kinerja?->approved_at ? Carbon::parse($kinerja->approved_at)->translatedFormat('d M Y H:i') : null,
                'catatan_revisi' => $kinerja?->catatan_revisi,
            ];

            // --- Tahap 2 Data ---
            $statusTahap2 = $kinerja?->status_tahap2 ?? 'belum_mulai';
            $tahap2HasData = $kinerja && in_array($statusTahap2, [
                'draft_guru', 'menunggu_penilaian', 'submitted', 'approved', 'revisi_guru', 'revisi_observer', 'revisi_keduanya'
            ]) && ($kinerja->status_tahap1 === 'approved' || $kinerja->tahap >= 2);

            $kesadaranRefleksiLabel = match ((int)($kinerja?->kesadaran_refleksi ?? 0)) {
                1 => 'Sadar Kesulitan (Rendah)',
                2 => 'Sadar Dampak (Sedang)',
                3 => 'Sadar Pengaruh & Berinisiatif (Tinggi)',
                default => 'Belum Dinilai'
            };

            $tahap2 = [
                'has_data' => $tahap2HasData,
                'status' => $statusTahap2,
                'status_label' => $this->getStatusTahap2Label($statusTahap2),
                'tempat_tahap2' => $kinerja?->tempat_tahap2 ?? '-',
                'tanggal_tahap2' => $kinerja?->tanggal_tahap2 ? Carbon::parse($kinerja->tanggal_tahap2)->translatedFormat('l, d F Y') : '-',
                'waktu_tahap2' => $kinerja?->waktu_tahap2 ? substr($kinerja->waktu_tahap2, 0, 5) : '-',
                'refleksi_rgtk' => $kinerja?->refleksi_rgtk,
                'refleksi_lain' => $kinerja?->refleksi_lain,
                'tujuan_tindak_lanjut' => $kinerja?->tujuan_tindak_lanjut ?? [],
                'upaya_tindak_lanjut' => $kinerja?->upaya_tindak_lanjut ?? [],
                'kapan_tindak_lanjut' => $kinerja?->kapan_tindak_lanjut ?? [],
                'kebutuhan_dukungan' => $kinerja?->kebutuhan_dukungan,
                'pengembangan_kompetensi' => $kinerja?->pengembangan_kompetensi ?? [],
                'tugas_tambahan' => $kinerja?->tugas_tambahan ?? [],
                'kesadaran_refleksi' => $kinerja?->kesadaran_refleksi,
                'kesadaran_refleksi_label' => $kesadaranRefleksiLabel,
                'catatan_kepsek_tahap2' => $kinerja?->catatan_kepsek_tahap2,
            ];

            // --- Tahap 3 Data ---
            $statusTahap3 = $kinerja?->status_tahap3 ?? 'belum_mulai';
            $tahap3HasData = $kinerja && in_array($statusTahap3, [
                'draft_guru', 'menunggu_penilaian', 'submitted', 'approved', 'revisi_guru', 'revisi_observer', 'revisi_keduanya'
            ]) && ($kinerja->status_tahap2 === 'approved' || $kinerja->tahap >= 3);

            $kesadaranRefleksiTahap3Label = match ((int)($kinerja?->kesadaran_refleksi_tahap3 ?? 0)) {
                1 => 'Sadar tantangan dan penyebabnya berada di luar kendali diri',
                2 => 'Sadar tantangan dan penyebabnya berada di dalam kendali diri',
                3 => 'Sadar tantangan dan penyebabnya berada di dalam kendali diri serta dampaknya',
                default => 'Belum Dinilai'
            };

            $upayaKompetensiTahap3Label = match ((int)($kinerja?->upaya_kompetensi_tahap3 ?? 0)) {
                1 => 'Melakukan upaya tindak lanjut',
                2 => 'Mempraktikkan inspirasi dari upaya tindak lanjut',
                3 => 'Membagikan praktik baik hasil inspirasi dari upaya tindak lanjut',
                default => 'Belum Dinilai'
            };

            $peningkatanKinerjaTahap3Label = match ((int)($kinerja?->peningkatan_kinerja_tahap3 ?? 0)) {
                1 => 'Ada upaya melakukan peningkatan kinerja',
                2 => 'Upaya melakukan peningkatan kinerja sejalan dengan ekspektasi atasan',
                3 => 'Upaya melakukan peningkatan kinerja mendapatkan respon positif dari peserta didik',
                default => 'Belum Dinilai'
            };

            $tahap3 = [
                'has_data' => $tahap3HasData,
                'status' => $statusTahap3,
                'status_label' => $this->getStatusTahap3Label($statusTahap3),
                'tempat_tahap3' => $kinerja?->tempat_tahap3 ?? '-',
                'tanggal_tahap3' => $kinerja?->tanggal_tahap3 ? Carbon::parse($kinerja->tanggal_tahap3)->translatedFormat('l, d F Y') : '-',
                'waktu_tahap3' => $kinerja?->waktu_tahap3 ? substr($kinerja->waktu_tahap3, 0, 5) : '-',
                'tantangan_perubahan' => $kinerja?->tantangan_perubahan ?? [],
                'kesadaran_refleksi_tahap3' => $kinerja?->kesadaran_refleksi_tahap3,
                'kesadaran_refleksi_tahap3_label' => $kesadaranRefleksiTahap3Label,
                'catatan_refleksi_tahap3' => $kinerja?->catatan_refleksi_tahap3,
                'inspirasi_didapat' => $kinerja?->inspirasi_didapat ?? [],
                'perubahan_praktik_tahap3' => $kinerja?->perubahan_praktik_tahap3,
                'upaya_kompetensi_tahap3' => $kinerja?->upaya_kompetensi_tahap3,
                'upaya_kompetensi_tahap3_label' => $upayaKompetensiTahap3Label,
                'catatan_upaya_tahap3' => $kinerja?->catatan_upaya_tahap3,
                'perubahan_praktik_kinerja_tahap3' => $kinerja?->perubahan_praktik_kinerja_tahap3,
                'rencana_mengatasi_tantangan' => $kinerja?->rencana_mengatasi_tantangan ?? [],
                'peningkatan_kinerja_tahap3' => $kinerja?->peningkatan_kinerja_tahap3,
                'peningkatan_kinerja_tahap3_label' => $peningkatanKinerjaTahap3Label,
                'catatan_peningkatan_tahap3' => $kinerja?->catatan_peningkatan_tahap3,
                'catatan_observer_tahap3' => $kinerja?->catatan_peningkatan_tahap3 ?? $kinerja?->catatan_observer_tahap3,
            ];

            // Penentuan Status Akhir
            $statusAkhir = $this->determineStatusAkhir($statusTahap1, $statusTahap2, $statusTahap3);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status_pegawai' => strtoupper($user->status_pegawai ?? '-'),
                'observer' => $observer ? [
                    'id' => $observer->id,
                    'name' => $observer->name,
                ] : null,
                'kinerja_id' => $kinerja?->id,
                'tahap1' => $tahap1,
                'tahap2' => $tahap2,
                'tahap3' => $tahap3,
                'status_akhir' => $statusAkhir,
            ];
        });

        // Statistik ringkasan
        $stats = [
            'total_guru' => $data->count(),
            'tahap1_selesai' => $data->where('tahap1.status', 'approved')->count(),
            'tahap2_selesai' => $data->where('tahap2.status', 'approved')->count(),
            'tahap3_selesai' => $data->where('tahap3.status', 'approved')->count(),
            'sedang_proses' => $data->filter(function ($item) {
                return in_array($item['status_akhir']['key'], [
                    'proses_tahap1', 'verifikasi_tahap1', 'proses_tahap2', 'verifikasi_tahap2', 'proses_tahap3', 'verifikasi_tahap3', 'revisi'
                ]);
            })->count(),
            'belum_mulai' => $data->where('status_akhir.key', 'belum_mulai')->count(),
        ];

        return Inertia::render('LaporanKinerjaTahunan/Index', [
            'data' => $data,
            'available_years' => $availableYears,
            'selected_year' => $selectedYear,
            'stats' => $stats,
        ]);
    }

    private function getStatusTahap1Label(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'pending' => 'Menunggu Verifikasi',
            'draft_observer' => 'Menunggu Penilaian Observer',
            'draft_guru' => 'Draft RHK Guru',
            'revisi_guru' => 'Perlu Revisi Guru',
            'revisi_observer' => 'Perlu Revisi Observer',
            'revisi_keduanya' => 'Perlu Revisi',
            default => 'Belum Mulai',
        };
    }

    private function getStatusTahap2Label(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui / Selesai',
            'submitted' => 'Menunggu Verifikasi',
            'menunggu_penilaian' => 'Menunggu Penilaian Observer',
            'draft_guru' => 'Draft Refleksi Guru',
            'revisi_guru' => 'Perlu Revisi Guru',
            'revisi_observer' => 'Perlu Revisi Observer',
            'revisi_keduanya' => 'Perlu Revisi',
            default => 'Belum Mulai',
        };
    }

    private function getStatusTahap3Label(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui / Selesai',
            'submitted' => 'Menunggu Verifikasi',
            'menunggu_penilaian' => 'Menunggu Penilaian Observer',
            'draft_guru' => 'Draft Refleksi Guru',
            'revisi_guru' => 'Perlu Revisi Guru',
            'revisi_observer' => 'Perlu Revisi Observer',
            'revisi_keduanya' => 'Perlu Revisi',
            default => 'Belum Mulai',
        };
    }

    private function determineStatusAkhir(string $statusTahap1, string $statusTahap2, string $statusTahap3): array
    {
        if ($statusTahap3 === 'approved') {
            return [
                'key' => 'selesai',
                'label' => 'Selesai Penuh (Tahap 3)',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
            ];
        }
        if ($statusTahap3 === 'submitted') {
            return [
                'key' => 'verifikasi_tahap3',
                'label' => 'Tahap 3 (Verifikasi)',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800'
            ];
        }
        if (in_array($statusTahap3, ['revisi_guru', 'revisi_observer', 'revisi_keduanya'])) {
            return [
                'key' => 'revisi',
                'label' => 'Revisi Tahap 3',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
            ];
        }
        if (in_array($statusTahap3, ['draft_guru', 'menunggu_penilaian'])) {
            return [
                'key' => 'proses_tahap3',
                'label' => 'Proses Tahap 3',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800'
            ];
        }
        if ($statusTahap2 === 'approved') {
            return [
                'key' => 'tahap2_selesai',
                'label' => 'Tahap 2 Disetujui',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800'
            ];
        }
        if ($statusTahap2 === 'submitted') {
            return [
                'key' => 'verifikasi_tahap2',
                'label' => 'Tahap 2 (Verifikasi)',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
            ];
        }
        if (in_array($statusTahap2, ['revisi_guru', 'revisi_observer', 'revisi_keduanya'])) {
            return [
                'key' => 'revisi',
                'label' => 'Revisi Tahap 2',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
            ];
        }
        if (in_array($statusTahap2, ['draft_guru', 'menunggu_penilaian'])) {
            return [
                'key' => 'proses_tahap2',
                'label' => 'Proses Tahap 2',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800'
            ];
        }
        if ($statusTahap1 === 'approved') {
            return [
                'key' => 'tahap1_selesai',
                'label' => 'Tahap 1 Disetujui',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800'
            ];
        }
        if ($statusTahap1 === 'pending') {
            return [
                'key' => 'verifikasi_tahap1',
                'label' => 'Tahap 1 (Verifikasi)',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
            ];
        }
        if (in_array($statusTahap1, ['revisi_guru', 'revisi_observer', 'revisi_keduanya'])) {
            return [
                'key' => 'revisi',
                'label' => 'Revisi Tahap 1',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
            ];
        }
        if (in_array($statusTahap1, ['draft_guru', 'draft_observer'])) {
            return [
                'key' => 'proses_tahap1',
                'label' => 'Proses Tahap 1',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800'
            ];
        }

        return [
            'key' => 'belum_mulai',
            'label' => 'Belum Mulai',
            'badge' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
        ];
    }
}
