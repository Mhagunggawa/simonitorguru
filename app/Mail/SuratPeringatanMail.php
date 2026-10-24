<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SuratPeringatanMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $guru;
    public $tipePeringatan;
    public $filePath;

    public function __construct($guru, $tipePeringatan, $filePath)
    {
        $this->guru = $guru;
        $this->tipePeringatan = $tipePeringatan;
        $this->filePath = $filePath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan ' . $this->tipePeringatan . ' - Kedisiplinan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.surat_peringatan',
        );
    }

    public function attachments(): array
    {
        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);

        $namaGuru = preg_replace(
            '/[^A-Za-z0-9_\-]/',
            '_',
            $this->guru->name
        );

        $namaFile =
            'Surat_' .
            str_replace(' ', '_', $this->tipePeringatan) .
            '_' .
            $namaGuru .
            '.' .
            $extension;

        $mime = match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return [
            Attachment::fromStorageDisk('public', $this->filePath)
                ->as($namaFile)
                ->withMime($mime),
        ];
    }
}