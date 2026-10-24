<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class JadwalMapel extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    protected $fillable = [
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'hari'
    ];

    public function kelas() { return $this->belongsTo(Kelas::class); }
    public function mataPelajaran() { return $this->belongsTo(MataPelajaran::class); }
    public function guru() { return $this->belongsTo(User::class, 'guru_id'); }
    public function jamPelajarans() { return $this->belongsToMany(JamPelajaran::class, 'jadwal_mapel_jam_pelajaran'); }
}
