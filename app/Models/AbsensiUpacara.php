<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class AbsensiUpacara extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tanggal',
        'user_id',
        'is_hadir',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_hadir' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
