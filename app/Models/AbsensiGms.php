<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiGms extends Model
{
    use LogsActivity;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'waktu_absen',
        'status',
        'is_terlambat',
        'foto_bukti',
        'keterangan',
        'confirmed_by',
    ];

    protected $casts = [
        'is_terlambat' => 'boolean',
    ];

    protected $appends = [
        'display_status',
    ];

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'Hadir' && ($this->is_terlambat || str_contains($this->keterangan ?? '', 'Terlambat'))) {
            return 'Hadir (Terlambat)';
        }
        return $this->status ?: 'Hadir';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function confirmedByUser()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
