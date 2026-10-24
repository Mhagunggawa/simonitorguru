<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settingan extends Model
{
    use LogsActivity;

    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $table = 'settingan';

    protected $fillable = [
        'nama_aplikasi',
        'nama_instansi',
        'logo_aplikasi',
        'last_backup_date',
        'last_mass_delete_date',
    ];

}
