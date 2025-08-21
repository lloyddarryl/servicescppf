<?php
// app/Mail/RendezVousAnnulationAdminMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\RendezVousDemande;

class RendezVousAnnulationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $demande;
    public $user;
    public $motifAnnulation;

    public function __construct(RendezVousDemande $demande, $user, $motifAnnulation = '')
    {
        $this->demande = $demande;
        $this->user = $user;
        $this->motifAnnulation = $motifAnnulation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[CPPF e-Services] 🚫 Rendez-vous annulé par l\'utilisateur - ' . $this->demande->numero_demande,
            from: env('MAIL_FROM_ADDRESS', 'nguidjoldarryl@gmail.com'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.rendez-vous-annulation-admin'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}