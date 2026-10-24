<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class JamPelajaran extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    protected $fillable = [
        'urutan_jam',
        'waktu_mulai',
        'waktu_selesai'
    ];

    public function jadwalMapels() { return $this->hasMany(JadwalMapel::class); }
}
