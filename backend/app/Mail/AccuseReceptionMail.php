<?php
// app/Mail/AccuseReceptionMail.php

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
use App\Services\AccuseReceptionService;

class AccuseReceptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reclamation;
    public $user;
    private $accuseService;

    public function __construct(Reclamation $reclamation, $user)
    {
        $this->reclamation = $reclamation;
        $this->user = $user;
        $this->accuseService = new AccuseReceptionService();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'noreply@cppf.ga'),
                'CPPF e-Services'
            ),
            to: [new Address(
                $this->user->email, 
                $this->user->prenoms . ' ' . $this->user->nom
            )],
            subject: "✅ Accusé de réception - Réclamation N° {$this->reclamation->numero_reclamation}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.accuse-reception',
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
            Log::info('📎 [ACCUSE-MAIL] Génération PDF en pièce jointe:', [
                'reclamation_id' => $this->reclamation->id,
                'numero' => $this->reclamation->numero_reclamation
            ]);

            // Générer l'accusé de réception PDF
            $pdfContent = $this->accuseService->genererAccuseReception($this->reclamation, $this->user);
            $filename = "Accuse_Reception_{$this->reclamation->numero_reclamation}.pdf";

            // Créer un fichier temporaire pour l'attachement
            $tempFile = tempnam(sys_get_temp_dir(), 'accuse_');
            file_put_contents($tempFile, $pdfContent);

            $attachments[] = Attachment::fromPath($tempFile)
                                      ->as($filename)
                                      ->withMime('application/pdf');

            Log::info('✅ [ACCUSE-MAIL] PDF généré et attaché:', [
                'filename' => $filename,
                'size' => strlen($pdfContent)
            ]);

            // Programmer la suppression du fichier temporaire après l'envoi
            register_shutdown_function(function() use ($tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            });

        } catch (\Exception $e) {
            Log::error('❌ [ACCUSE-MAIL] Erreur génération PDF:', [
                'reclamation_id' => $this->reclamation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $attachments;
    }
}