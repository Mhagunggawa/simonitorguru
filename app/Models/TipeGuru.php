<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeGuru extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $fillable = [
        'nama_tipe',
        'akses_piket_kelas',
        'akses_gms',
    ];

    protected $casts = [
        'akses_piket_kelas' => 'boolean',
        'akses_gms' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
