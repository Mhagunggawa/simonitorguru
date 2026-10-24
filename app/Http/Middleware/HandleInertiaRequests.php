<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\View\Components\App;
use App\Models\Settingan;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settingan = Settingan::first();
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'tipe_guru' => $request->user()->tipeGuru,
                    'has_jadwal_gms_hari_ini' => $request->user()->jadwalGms()->where('hari', [
                        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
                    ][\Carbon\Carbon::now()->format('l')])->exists(),
                ]) : null,
            ],
            'settings' => [
                'nama_aplikasi' => $settingan?->nama_aplikasi ?? 'Simonitorguru',
                'logo_aplikasi' => $settingan?->logo_aplikasi
                    ? asset('storage/' . $settingan->logo_aplikasi)
                    : asset('apple-touch-icon.png'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'pending_approvals_count' => fn () => (
                $request->user() && in_array($request->user()->role, ['super admin', 'admin', 'wakasek'])
                ? \App\Models\Kinerja::where('periode', 'Januari-Desember ' . \Carbon\Carbon::now()->year)
                    ->where(function($q) {
                        $q->where('status_tahap1', 'pending')
                          ->orWhere('status_tahap2', 'submitted');
                    })->count()
                : 0
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
