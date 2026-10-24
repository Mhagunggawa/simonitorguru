<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Traits\LogsActivity;

#[Fillable(['guru_id', 'observer_id', 'tempat', 'periode', 'tanggal', 'waktu', 'rekomendasi', 'video_link', 'tahap', 'status_tahap1', 'approved_by', 'approved_at', 'tempat_tahap2', 'tanggal_tahap2', 'waktu_tahap2', 'refleksi_rgtk', 'refleksi_lain', 'tujuan_tindak_lanjut', 'upaya_tindak_lanjut', 'kapan_tindak_lanjut', 'kebutuhan_dukungan', 'pengembangan_kompetensi', 'tugas_tambahan', 'catatan_kepsek_tahap2', 'kesadaran_refleksi', 'status_tahap2', 'tempat_tahap3', 'tanggal_tahap3', 'waktu_tahap3', 'tantangan_perubahan', 'kesadaran_refleksi_tahap3', 'catatan_refleksi_tahap3', 'inspirasi_didapat', 'perubahan_praktik_tahap3', 'upaya_kompetensi_tahap3', 'catatan_upaya_tahap3', 'perubahan_praktik_kinerja_tahap3', 'rencana_mengatasi_tantangan', 'peningkatan_kinerja_tahap3', 'catatan_peningkatan_tahap3', 'catatan_observer_tahap3', 'status_tahap3', 'revisi_tahap', 'revisi_target', 'catatan_revisi', 'rejected_by', 'rejected_at'])]
class Kinerja extends Model
{
    use LogsActivity;
    protected $casts = [
        'tujuan_tindak_lanjut' => 'array',
        'upaya_tindak_lanjut' => 'array',
        'kapan_tindak_lanjut' => 'array',
        'pengembangan_kompetensi' => 'array',
        'tugas_tambahan' => 'array',
        'tantangan_perubahan' => 'array',
        'inspirasi_didapat' => 'array',
        'rencana_mengatasi_tantangan' => 'array',
        'rejected_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function guru() { return $this->belongsTo(User::class, 'guru_id'); }
    public function observer() { return $this->belongsTo(User::class, 'observer_id'); }
    public function fokuses() { return $this->hasMany(KinerjaFokus::class, 'kinerja_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }
}
