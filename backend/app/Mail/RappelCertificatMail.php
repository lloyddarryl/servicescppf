<?php

namespace App\Mail;

use App\Models\Retraite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RappelCertificatMail extends Mailable
{
    use Queueable, SerializesModels;

    public $retraite;

    /**
     * Create a new message instance.
     */
    public function __construct(Retraite $retraite)
    {
        $this->retraite = $retraite;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel : Dépôt de votre certificat de vie',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rappel-certificat',
            text: 'emails.rappel-certificat-text',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}