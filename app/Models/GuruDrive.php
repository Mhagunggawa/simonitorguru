<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruDrive extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'guru_id',
        'google_drive_url',
        'share_to_guru',
        'share_to_observer',
    ];

    protected $casts = [
        'share_to_guru' => 'boolean',
        'share_to_observer' => 'boolean',
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
