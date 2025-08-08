<?php
// app/Mail/ReclamationCreatedMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\Reclamation;

class ReclamationCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reclamation;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Reclamation $reclamation, $user)
    {
        $this->reclamation = $reclamation;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [config('app.reclamation_email', 'nguidjoldarryl@gmail.com')],
            subject: "Nouvelle réclamation - N° {$this->reclamation->numero_reclamation}",
            replyTo: [$this->user->email => $this->user->prenoms . ' ' . $this->user->nom]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reclamation-created',
            with: [
                'reclamation' => $this->reclamation,
                'user' => $this->user,
                'typeReclamation' => $this->reclamation->type_reclamation_info,
                'prioriteInfo' => $this->reclamation->priorite_info
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->reclamation->documents) {
            foreach ($this->reclamation->documents as $document) {
                $filePath = storage_path('app/public/' . $document['chemin']);
                
                if (file_exists($filePath)) {
                    $attachments[] = Attachment::fromPath($filePath)
                                              ->as($document['nom_original'])
                                              ->withMime($this->getMimeType($document['type']));
                }
            }
        }

        return $attachments;
    }

    /**
     * Obtenir le MIME type selon l'extension
     */
    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}

