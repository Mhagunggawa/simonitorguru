<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $fillable = [
        'nama_jurusan'
    ];


    public function titiks() { return $this->belongsToMany(Titik::class, 'titik_jurusan'); }
    public function kelas() { return $this->hasMany(Kelas::class); }
}
