<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Tahap3DisetujuiMail extends Mailable
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
            subject: '🎉 Selamat! Formulir Tahap 3 (Refleksi Tindak Lanjut Kinerja) Anda Telah Disetujui (ACC)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tahap3_disetujui',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
