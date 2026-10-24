<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VideoObservasiDiunggahMail extends Mailable
{
    use Queueable, SerializesModels;

    public $guru;
    public $observer;
    public $videoLink;
    public $periode;

    public function __construct($guru, $observer, $videoLink, $periode)
    {
        $this->guru = $guru;
        $this->observer = $observer;
        $this->videoLink = $videoLink;
        $this->periode = $periode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📢 Pemberitahuan: Video Observasi Diunggah - Menunggu Verifikasi (ACC) Formulir Tahap 1',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.video_observasi_diunggah',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
