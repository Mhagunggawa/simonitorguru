<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nama_kelas',
        'jurusan_id',
        'tingkat_kelas_id',
    ];

    public function tingkatKelas()
    {
        return $this->belongsTo(TingkatKelas::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function jadwalMapels()
    {
        return $this->hasMany(JadwalMapel::class);
    }
}
