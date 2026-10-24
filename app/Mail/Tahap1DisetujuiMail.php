<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Tahap1DisetujuiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $guru;
    public $approver;
    public $periode;

    public function __construct($guru, $approver, $periode)
    {
        $this->guru = $guru;
        $this->approver = $approver;
        $this->periode = $periode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📢 Pemberitahuan: Formulir Observasi Kinerja (Tahap 1) Telah Disetujui (ACC)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tahap1_disetujui',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
