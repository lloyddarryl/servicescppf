<?php
// app/Mail/RendezVousReponseUserMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\RendezVousDemande;

class RendezVousReponseUserMail extends Mailable
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
        $sujetStatut = [
            'accepte' => 'Rendez-vous confirmé',
            'refuse' => 'Demande de rendez-vous refusée',
            'reporte' => 'Rendez-vous reporté',
            'annule' => 'Rendez-vous annulé'
        ];

        $sujet = $sujetStatut[$this->demande->statut] ?? 'Mise à jour de votre demande de rendez-vous';

        return new Envelope(
            subject: '[CPPF e-Services] ' . $sujet . ' - ' . $this->demande->numero_demande,
            from: env('MAIL_FROM_ADDRESS', 'nguidjoldarryl@gmail.com'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.rendez-vous-reponse-user',
            text: 'emails.rendez-vous-reponse-user-text'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}