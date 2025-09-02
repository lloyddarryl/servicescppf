<?php
// app/Mail/RendezVousRappelMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\RendezVousDemande;

class RendezVousRappelMail extends Mailable
{
    use Queueable, SerializesModels;

    public $demande;
    public $user;

    public function __construct(RendezVousDemande $demande, $user)
    {
        $this->demande = $demande;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[CPPF e-Services] Rappel - Rendez-vous demain - ' . $this->demande->numero_demande,
            from: env('MAIL_FROM_ADDRESS', 'noreply@cppf.ga'),
            replyTo: [], // Pas de reply-to = no-reply
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.rendez-vous-rappel',
            text: 'emails.rendez-vous-rappel-text'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}