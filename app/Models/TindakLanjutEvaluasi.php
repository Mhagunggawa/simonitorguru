<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TindakLanjutEvaluasi extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipe_peringatan',
        'file_dokumen',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
