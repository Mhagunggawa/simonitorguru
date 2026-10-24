<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanGms extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $fillable = ['waktu_mulai', 'waktu_selesai'];
}
