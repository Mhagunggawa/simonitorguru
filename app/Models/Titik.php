<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Titik extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;
    protected $fillable = [
        'nama_titik'
    ];

    public function jurusans() { return $this->belongsToMany(Jurusan::class, 'titik_jurusan'); }
}
