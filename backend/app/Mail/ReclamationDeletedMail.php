<?php
// app/Mail/ReclamationDeletedMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReclamationDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $numeroReclamation;
    public $typeReclamation;
    public $user;
    public $motifSuppression;

    /**
     * Create a new message instance.
     */
    public function __construct($numeroReclamation, $typeReclamation, $user, $motifSuppression = null)
    {
        $this->numeroReclamation = $numeroReclamation;
        $this->typeReclamation = $typeReclamation;
        $this->user = $user;
        $this->motifSuppression = $motifSuppression;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [config('app.reclamation_email', 'nguidjoldarryl@gmail.com')],
            subject: "Réclamation supprimée - N° {$this->numeroReclamation}",
            replyTo: [$this->user->email => $this->user->prenoms . ' ' . $this->user->nom]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reclamation-deleted',
            with: [
                'numeroReclamation' => $this->numeroReclamation,
                'typeReclamation' => $this->typeReclamation,
                'user' => $this->user,
                'motifSuppression' => $this->motifSuppression
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}