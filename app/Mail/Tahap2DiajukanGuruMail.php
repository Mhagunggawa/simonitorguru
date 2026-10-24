<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Tahap2DiajukanGuruMail extends Mailable
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
            subject: '📢 Pemberitahuan: Refleksi Tindak Lanjut Kinerja (Tahap 2) Telah Diajukan Guru',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tahap2_diajukan_guru',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
