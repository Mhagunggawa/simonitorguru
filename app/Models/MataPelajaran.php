<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    protected $fillable = [
        'nama_mapel',
        'guru_id'
    ];

    public function jadwalMapels() { return $this->hasMany(JadwalMapel::class); }
}
