<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KinerjaRevisiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipient;
    public $role;
    public $guru;
    public $observer;
    public $tahap;
    public $target;
    public $catatanRevisi;
    public $rejecter;
    public $periode;

    public function __construct($recipient, $role, $guru, $observer, $tahap, $target, $catatanRevisi, $rejecter, $periode)
    {
        $this->recipient = $recipient;
        $this->role = $role;
        $this->guru = $guru;
        $this->observer = $observer;
        $this->tahap = $tahap;
        $this->target = $target;
        $this->catatanRevisi = $catatanRevisi;
        $this->rejecter = $rejecter;
        $this->periode = $periode;
    }

    public function envelope(): Envelope
    {
        $tahapLabel = $this->tahap == 3 ? 'Tahap 3 (Refleksi Pasca Tindak Lanjut)' : ($this->tahap == 2 ? 'Tahap 2 (Tindak Lanjut)' : 'Tahap 1 (Observasi Praktik)');
        if ($this->target === 'keduanya') {
            $subject = $this->role === 'guru' 
                ? '📢 Pemberitahuan: Formulir Observasi Kinerja ' . $tahapLabel . ' Memerlukan Diskusi & Revisi' 
                : '📢 Pemberitahuan: Formulir Observasi Kinerja ' . $tahapLabel . ' Memerlukan Diskusi & Revisi Bersama Guru (' . ($this->guru->name ?? '') . ')';
        } elseif ($this->role === 'guru') {
            $subject = '📢 Pemberitahuan: Formulir Observasi Kinerja ' . $tahapLabel . ' Memerlukan Revisi';
        } else {
            $subject = '📢 Pemberitahuan: Penilaian Observasi Kinerja ' . $tahapLabel . ' Memerlukan Revisi (' . ($this->guru->name ?? '') . ')';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.kinerja_revisi',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
