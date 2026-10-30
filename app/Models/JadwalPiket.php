<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JadwalPiket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tanggal_piket',
        'titik_id',
        'guru_piket_id',
        'status',
        'keterangan'
    ];

    public function titik() { return $this->belongsTo(Titik::class); }
    public function guruPiket() { return $this->belongsTo(User::class, 'guru_piket_id'); }
    public function jamPelajarans() { return $this->belongsToMany(JamPelajaran::class, 'jadwal_piket_jam_pelajaran'); }
    public function rekapKehadirans() { return $this->hasMany(RekapKehadiran::class); }

    /**
     * Hitung status kehadiran guru piket secara dinamis & konsisten
     */
    public function getEvaluatedStatus(): string
    {
        if ($this->status === 'Izin') return 'Izin';
        if ($this->status === 'Sakit') return 'Sakit';
        if ($this->status === 'Hadir' || $this->rekapKehadirans->count() > 0) return 'Hadir';

        // Cari jam pelajaran piket terakhir
        $latestJam = $this->jamPelajarans->sortByDesc('waktu_selesai')->first();
        if ($latestJam && $latestJam->waktu_selesai) {
            $waktuSelesai = substr($latestJam->waktu_selesai, 0, 5);
            if (strlen($waktuSelesai) <= 5) $waktuSelesai .= ':00';
            $endTime = Carbon::parse($this->tanggal_piket . ' ' . $waktuSelesai);
            if (Carbon::now()->greaterThan($endTime)) {
                return 'Alpha';
            }
        } elseif (Carbon::parse($this->tanggal_piket)->endOfDay()->isPast()) {
            return 'Alpha';
        }

        return 'Terjadwal';
    }
}
