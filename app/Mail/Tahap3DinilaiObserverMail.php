<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Tahap3DinilaiObserverMail extends Mailable
{
    use Queueable, SerializesModels;

    public $guru;
    public $observer;
    public $periode;

    public function __construct($guru, $observer, $periode)
    {
        $this->guru = $guru;
        $this->observer = $observer;
        $this->periode = $periode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📢 Pemberitahuan: Refleksi Tindak Lanjut Kinerja (Tahap 3) Telah Dinilai - Menunggu Verifikasi (ACC)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tahap3_dinilai_observer',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
