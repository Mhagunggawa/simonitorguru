<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PengaturanPiket extends Model
{
    use LogsActivity;

    protected $table = 'pengaturan_pikets';

    protected $fillable = [
        'waktu_mulai',
        'waktu_selesai',
        'keterangan',
    ];

    /**
     * Dapatkan satu-satunya baris pengaturan piket, buat jika belum ada
     */
    public static function getPengaturan(): self
    {
        return static::firstOrCreate([], [
            'waktu_mulai' => '06:30:00',
            'waktu_selesai' => '16:00:00',
            'keterangan' => 'Batas waktu default pelaporan tugas piket harian',
        ]);
    }
}

