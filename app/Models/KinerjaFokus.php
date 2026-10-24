<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['kinerja_id', 'nama_fokus', 'perilaku_dianjurkan', 'perilaku_dihindari', 'penilaians', 'penilaian', 'catatan'])]
class KinerjaFokus extends Model
{
    protected function casts(): array
    {
        return [
            'perilaku_dianjurkan' => 'array',
            'perilaku_dihindari' => 'array',
            'penilaians' => 'array',
        ];
    }

    public function kinerja() { return $this->belongsTo(Kinerja::class, 'kinerja_id'); }
}
