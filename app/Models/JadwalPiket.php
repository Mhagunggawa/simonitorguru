<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class JadwalPiket extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    protected $fillable = [
        'tanggal_piket',
        'titik_id',
        'guru_piket_id'
    ];

    public function titik() { return $this->belongsTo(Titik::class); }
    public function guruPiket() { return $this->belongsTo(User::class, 'guru_piket_id'); }
    public function jamPelajarans() { return $this->belongsToMany(JamPelajaran::class, 'jadwal_piket_jam_pelajaran'); }
    public function rekapKehadirans() { return $this->hasMany(RekapKehadiran::class); }

}
