<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanGms extends Model
{
    use LogsActivity;
    use HasFactory;

    protected $fillable = [
        'waktu_mulai',
        'waktu_selesai',
        'jam_normal_mulai',
        'jam_normal_selesai',
        'jam_terlambat_mulai',
        'jam_terlambat_selesai'
    ];
}
