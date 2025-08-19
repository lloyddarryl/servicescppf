<?php
// app/Mail/RendezVousDemandeAdminMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\RendezVousDemande;

class RendezVousDemandeAdminMail extends Mailable
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
            subject: '[CPPF e-Services] Nouvelle demande de rendez-vous - ' . $this->demande->numero_demande,
            from: env('MAIL_FROM_ADDRESS', 'nguidjoldarryl@gmail.com'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.rendez-vous-demande-admin',
            text: 'emails.rendez-vous-demande-admin-text'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}