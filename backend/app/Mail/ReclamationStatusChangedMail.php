<?php
// app/Mail/ReclamationStatusChangedMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Reclamation;
use App\Models\ReclamationHistorique;

class ReclamationStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reclamation;
    public $user;
    public $historique;

    /**
     * Create a new message instance.
     */
    public function __construct(Reclamation $reclamation, $user, ReclamationHistorique $historique)
    {
        $this->reclamation = $reclamation;
        $this->user = $user;
        $this->historique = $historique;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->user->email => $this->user->prenoms . ' ' . $this->user->nom],
            subject: "Mise à jour de votre réclamation - N° {$this->reclamation->numero_reclamation}"
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reclamation-status-changed',
            with: [
                'reclamation' => $this->reclamation,
                'user' => $this->user,
                'historique' => $this->historique,
                'ancienStatut' => $this->historique->ancien_statut_libelle,
                'nouveauStatut' => $this->historique->nouveau_statut_libelle
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

