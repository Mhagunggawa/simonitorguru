<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RencanaKerjaDiajukanMail extends Mailable
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
            subject: '📢 Pemberitahuan: Guru Telah Mengisi Rencana Hasil Kerja Observasi Kinerja',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rencana_kerja_diajukan',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
