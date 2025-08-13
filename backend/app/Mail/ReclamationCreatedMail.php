<?php
// app/Mail/ReclamationCreatedMail.php - VERSION AVEC PIÈCES JOINTES

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Reclamation;

class ReclamationCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reclamation;
    public $user;

    public function __construct(Reclamation $reclamation, $user)
    {
        $this->reclamation = $reclamation;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        $typeReclamationNom = isset($this->reclamation->type_reclamation_info['nom'])
            ? $this->reclamation->type_reclamation_info['nom']
            : $this->reclamation->type_reclamation;

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
            subject: "🆘 Réclamation CPPF N° {$this->reclamation->numero_reclamation} - {$typeReclamationNom}"
        );
    }

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

    public function attachments(): array
    {
        $attachments = [];

        try {
            // ✅ Ajouter tous les documents en pièces jointes
            if ($this->reclamation->documents && is_array($this->reclamation->documents)) {
                Log::info('📎 Traitement des documents pour email:', [
                    'count' => count($this->reclamation->documents),
                    'documents' => $this->reclamation->documents
                ]);

                foreach ($this->reclamation->documents as $index => $document) {
                    $filePath = storage_path('app/public/' . $document['chemin']);
                    
                    Log::info("📄 Document {$index}:", [
                        'nom_original' => $document['nom_original'],
                        'chemin' => $document['chemin'],
                        'file_path' => $filePath,
                        'file_exists' => file_exists($filePath),
                        'file_size' => file_exists($filePath) ? filesize($filePath) : 'N/A'
                    ]);
                    
                    if (file_exists($filePath)) {
                        $attachments[] = Attachment::fromPath($filePath)
                                                  ->as($document['nom_original'])
                                                  ->withMime($this->getMimeType($document['type']));
                        
                        Log::info("✅ Document ajouté en pièce jointe: {$document['nom_original']}");
                    } else {
                        Log::error("❌ Fichier introuvable: {$filePath}");
                    }
                }
            } else {
                Log::info('ℹ️ Aucun document à attacher');
            }
        } catch (\Exception $e) {
            Log::error('💥 Erreur lors de l\'ajout des pièces jointes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('📎 Total pièces jointes préparées:', ['count' => count($attachments)]);
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