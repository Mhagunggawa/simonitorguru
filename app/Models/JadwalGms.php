<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalGms extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $fillable = ['user_id', 'hari', 'waktu_mulai', 'waktu_selesai'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
