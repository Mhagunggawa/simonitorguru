<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TingkatKelas extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }
}
