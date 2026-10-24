<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinPiket extends Model
{
    use LogsActivity;

    use HasFactory;

    protected $fillable = [
        'jadwal_piket_id',
        'jam_pelajaran_id',
        'guru_piket_id',
        'status',
    ];

    public function jadwalPiket()
    {
        return $this->belongsTo(JadwalPiket::class);
    }

    public function jamPelajaran()
    {
        return $this->belongsTo(JamPelajaran::class);
    }

    public function guruPiket()
    {
        return $this->belongsTo(User::class, 'guru_piket_id');
    }
}
