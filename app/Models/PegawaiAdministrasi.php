<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PegawaiAdministrasi extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nama',
    ];

    public function absensiUpacaras()
    {
        return $this->hasMany(AbsensiUpacara::class);
    }
}

