<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Tahap2DisetujuiMail extends Mailable
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
            subject: '📢 Pemberitahuan: Tindak Lanjut Observasi Kinerja (Tahap 2) Telah Selesai & Disetujui (ACC)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tahap2_disetujui',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
