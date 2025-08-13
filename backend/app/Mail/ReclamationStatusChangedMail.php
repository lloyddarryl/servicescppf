<?php
// app/Mail/ReclamationDeletedMail.php - VERSION CORRIGÉE

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ReclamationDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $numeroReclamation;
    public $typeReclamation;
    public $user;
    public $motifSuppression;

    public function __construct($numeroReclamation, $typeReclamation, $user, $motifSuppression = null)
    {
        $this->numeroReclamation = $numeroReclamation;
        $this->typeReclamation = $typeReclamation;
        $this->user = $user;
        $this->motifSuppression = $motifSuppression;
    }

    public function envelope(): Envelope
    {
        // ✅ Email apparaît comme envoyé par l'utilisateur qui supprime
        return new Envelope(
            from: new Address(
                $this->user->email, 
                $this->user->prenoms . ' ' . $this->user->nom . ' (via CPPF e-Services)'
            ),
            to: [new Address(
                env('APP_RECLAMATION_EMAIL', 'nguidjoldarryl@gmail.com'),
                'Service Réclamations CPPF'
            )],
            replyTo: [new Address(
                $this->user->email, 
                $this->user->prenoms . ' ' . $this->user->nom
            )],
            subject: "❌ Réclamation supprimée - N° {$this->numeroReclamation}"
        );
    }

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

    public function attachments(): array
    {
        return [];
    }
}