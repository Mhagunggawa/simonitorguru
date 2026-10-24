<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class RekapKehadiran extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    protected $fillable = [
        'jadwal_mapel_id',
        'jadwal_piket_id',
        'status_kehadiran',
        'foto_dokumentasi',
        'keterangan',
        'is_terlambat'
    ];

    public function jadwalMapel() { return $this->belongsTo(JadwalMapel::class); }
    public function jadwalPiket() { return $this->belongsTo(JadwalPiket::class); }
}
