<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\DocumentRetraite;
use App\Models\Retraite;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Obtenir tous les documents d'un retraité avec notifications
     */
    public function index(Request $request)
    {
        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }

            $documents = DocumentRetraite::where('retraite_id', $retraite->id)
                                       ->where('statut', 'actif')
                                       ->orderBy('date_depot', 'desc')
                                       ->get()
                                       ->map(function ($document) {
                                           return [
                                               'id' => $document->id,
                                               'nom_original' => $document->nom_original,
                                               'type_document' => $document->type_document,
                                               'nom_type' => $document->type_document === 'certificat_vie' ? 'Certificat de Vie' : 'Autre Document',
                                               'icone_type' => $document->type_document === 'certificat_vie' ? '📋' : '📄',
                                               'description' => $document->description,
                                               'taille_formatee' => $this->formatFileSize($document->taille_fichier),
                                               'extension' => $document->extension,
                                               'statut' => $document->statut,
                                               'date_depot' => $document->date_depot->format('d/m/Y H:i'),
                                               'date_emission' => $document->date_emission?->format('d/m/Y'),
                                               'date_expiration' => $document->date_expiration?->format('d/m/Y'),
                                               'autorite_emission' => $document->autorite_emission,
                                               'is_expire' => $document->date_expiration ? $document->date_expiration->isPast() : false,
                                               'expire_bientot' => $document->date_expiration ? $document->date_expiration->diffInDays() <= 60 : false,
                                               'jours_avant_expiration' => $document->date_expiration ? now()->diffInDays($document->date_expiration, false) : null,
                                               'url_telechargement' => url("/api/retraites/documents/{$document->id}/download")
                                           ];
                                       });

            $statistiques = [
                'total_documents' => $documents->count(),
                'certificats_vie' => $documents->where('type_document', 'certificat_vie')->count(),
                'autres_documents' => $documents->where('type_document', 'autre')->count(),
                'documents_expires' => $documents->where('is_expire', true)->count()
            ];

            return response()->json([
                'success' => true,
                'retraite' => [
                    'id' => $retraite->id,
                    'nom_complet_avec_titre' => $retraite->prenoms . ' ' . $retraite->nom,
                    'numero_pension' => $retraite->numero_pension
                ],
                'documents' => $documents,
                'notifications' => DocumentRetraite::getNotificationsCertificat($retraite->id),
                'statistiques' => $statistiques,
                'limites' => [
                    'max_fichiers' => 3,
                    'taille_max_mo' => 5,
                    'extensions_autorisees' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur DocumentController::index:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents'
            ], 500);
        }
    }

    /**
     * Déposer de nouveaux documents
     */
    public function store(Request $request)
    {
        Log::info('=== DÉBUT UPLOAD DOCUMENTS ===');

        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }

            // Validation
            if (!$request->hasFile('documents') || !$request->has('types')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documents et types requis'
                ], 400);
            }

            $files = $request->file('documents');
            $types = $request->input('types');

            if (!is_array($files) || !is_array($types) || count($files) !== count($types)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données incohérentes'
                ], 400);
            }

            if (count($files) > 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 3 fichiers autorisés'
                ], 400);
            }

            // Validation des fichiers
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Fichier {$index} invalide"
                    ], 400);
                }

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Extension non autorisée: {$extension}"
                    ], 400);
                }

                $fileSize = $file->getSize();
                if ($fileSize > $maxSize) {
                    return response()->json([
                        'success' => false,
                        'message' => "Fichier trop volumineux: " . round($fileSize/1024/1024, 2) . "MB"
                    ], 400);
                }
            }

            DB::beginTransaction();

            $documentsCreated = [];
            $dossierDestination = "documents/retraites/{$retraite->id}";
            $cheminCompletDossier = storage_path("app/{$dossierDestination}");

            // Créer le dossier
            if (!file_exists($cheminCompletDossier)) {
                mkdir($cheminCompletDossier, 0755, true);
            }

            // Traitement des fichiers
            foreach ($files as $index => $file) {
                $type = $types[$index];
                $description = $request->input("descriptions.{$index}") ?: 
                             ($type === 'autre' ? 'Document personnel' : null);
                $dateEmission = $request->input("dates_emission.{$index}") ?: 
                              ($type === 'certificat_vie' ? now()->format('Y-m-d') : null);
                $autoriteEmission = $request->input("autorites_emission.{$index}") ?: 
                                  ($type === 'certificat_vie' ? 'Autorité compétente' : null);

                // Nom unique
                $extension = strtolower($file->getClientOriginalExtension());
                $timestamp = now()->format('Y-m-d_H-i-s');
                $random = Str::random(8);
                $nomFichier = "retraite_{$retraite->id}_{$timestamp}_{$random}.{$extension}";

                $cheminFichierAbsolu = $cheminCompletDossier . '/' . $nomFichier;
                $cheminFichierRelatif = $dossierDestination . '/' . $nomFichier;

                // Déplacer le fichier
                if (!$file->move($cheminCompletDossier, $nomFichier)) {
                    throw new \Exception("Erreur déplacement fichier {$index}");
                }

                // Vérifier que le fichier existe
                if (!file_exists($cheminFichierAbsolu)) {
                    throw new \Exception("Fichier non créé: {$cheminFichierAbsolu}");
                }

                $tailleFichier = filesize($cheminFichierAbsolu);
                if ($tailleFichier === false || $tailleFichier === 0) {
                    throw new \Exception("Fichier vide: {$cheminFichierAbsolu}");
                }

                // Date d'expiration
                $dateExpiration = null;
                if ($type === 'certificat_vie' && $dateEmission) {
                    $dateExpiration = Carbon::parse($dateEmission)->addMonths(12);
                }

                // Créer en base
                $document = DocumentRetraite::create([
                    'retraite_id' => $retraite->id,
                    'nom_original' => $file->getClientOriginalName(),
                    'nom_fichier' => $nomFichier,
                    'chemin_fichier' => $cheminFichierRelatif,
                    'type_document' => $type,
                    'description' => $description,
                    'taille_fichier' => $tailleFichier,
                    'extension' => $extension,
                    'date_emission' => $dateEmission,
                    'date_expiration' => $dateExpiration,
                    'autorite_emission' => $autoriteEmission,
                    'date_depot' => now(),
                    'statut' => 'actif',
                    'notifie_par_email' => false
                ]);

                $documentsCreated[] = $document;

                Log::info("Document {$index} créé", [
                    'document_id' => $document->id,
                    'chemin_absolu' => $cheminFichierAbsolu,
                    'taille' => $tailleFichier
                ]);
            }

            DB::commit();

            // Envoi email APRÈS commit
            try {
                $this->envoyerNotificationEmail($retraite, collect($documentsCreated));
                
                // Marquer comme notifiés
                DocumentRetraite::whereIn('id', collect($documentsCreated)->pluck('id'))
                    ->update(['notifie_par_email' => true]);
                    
            } catch (\Exception $e) {
                Log::error('Erreur email (non bloquante)', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => count($documentsCreated) . ' document(s) déposé(s) avec succès',
                'documents' => collect($documentsCreated)->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'nom_original' => $doc->nom_original,
                        'type_document' => $doc->type_document,
                        'date_depot' => $doc->date_depot->format('d/m/Y H:i'),
                        'taille' => $this->formatFileSize($doc->taille_fichier)
                    ];
                })
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un document
     */
    public function download(Request $request, $id)
    {
        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
            }
            
            $document = DocumentRetraite::where('id', $id)
                                      ->where('retraite_id', $retraite->id)
                                      ->where('statut', 'actif')
                                      ->first();

            if (!$document) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Document non trouvé'
                ], 404);
            }

            $cheminAbsolu = storage_path('app/' . $document->chemin_fichier);
            
            if (!file_exists($cheminAbsolu)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Fichier introuvable'
                ], 404);
            }

            return response()->download($cheminAbsolu, $document->nom_original, [
                'Content-Type' => $this->getMimeType($document->extension)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Erreur téléchargement'
            ], 500);
        }
    }

   public function destroy(Request $request, $id)
{
    try {
        $retraite = $request->user();
        
        if (!($retraite instanceof \App\Models\Retraite)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }
        
        $document = DocumentRetraite::where('id', $id)
                                  ->where('retraite_id', $retraite->id)
                                  ->where('statut', 'actif')
                                  ->first();
        
        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }
        
        DB::beginTransaction();
        
        // Supprimer fichier physique
        $cheminAbsolu = storage_path('app/' . $document->chemin_fichier);
        if (file_exists($cheminAbsolu)) {
            unlink($cheminAbsolu);
        }
        
        // ✅ SUPPRESSION RÉELLE
        $document->delete();
        
        DB::commit();
        
        Log::info('Document supprimé', [
            'document_id' => $id,
            'retraite_id' => $retraite->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur suppression', [
            'document_id' => $id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur suppression'
        ], 500);
    }
}

    /**
     * CORRECTION PRINCIPALE: Envoi email avec pièces jointes
     */
    private function envoyerNotificationEmail($retraite, $documents)
    {
        try {
            if (!config('app.reclamation_email')) {
                Log::error('Email destination non configuré');
                return;
            }

            Log::info('DÉBUT envoi email notification', [
                'retraite_id' => $retraite->id,
                'nb_documents' => $documents->count(),
                'email_destination' => config('app.reclamation_email')
            ]);

            // ÉTAPE 1: Vérifier TOUS les fichiers avant de construire l'email
            $fichiersValides = [];
            foreach ($documents as $document) {
                $cheminAbsolu = storage_path('app/' . $document->chemin_fichier);
                
                Log::info('Vérification fichier pour email', [
                    'document_id' => $document->id,
                    'nom_original' => $document->nom_original,
                    'chemin_bdd' => $document->chemin_fichier,
                    'chemin_absolu' => $cheminAbsolu,
                    'file_exists' => file_exists($cheminAbsolu),
                    'file_size' => file_exists($cheminAbsolu) ? filesize($cheminAbsolu) : 0,
                    'readable' => file_exists($cheminAbsolu) && is_readable($cheminAbsolu)
                ]);
                
                if (file_exists($cheminAbsolu) && is_readable($cheminAbsolu) && filesize($cheminAbsolu) > 0) {
                    $fichiersValides[] = [
                        'document' => $document,
                        'chemin_absolu' => $cheminAbsolu,
                        'taille' => filesize($cheminAbsolu)
                    ];
                    Log::info('Fichier VALIDÉ pour email', [
                        'document_id' => $document->id,
                        'taille' => filesize($cheminAbsolu)
                    ]);
                } else {
                    Log::error('Fichier NON VALIDE pour email', [
                        'document_id' => $document->id,
                        'chemin' => $cheminAbsolu,
                        'exists' => file_exists($cheminAbsolu),
                        'readable' => file_exists($cheminAbsolu) ? is_readable($cheminAbsolu) : false,
                        'size' => file_exists($cheminAbsolu) ? filesize($cheminAbsolu) : 0
                    ]);
                }
            }

            if (empty($fichiersValides)) {
                Log::error('AUCUN fichier valide pour email - ABANDON');
                return;
            }

            Log::info('Fichiers validés pour email', [
                'nb_valides' => count($fichiersValides),
                'nb_total' => $documents->count()
            ]);

            // ÉTAPE 2: Préparer les données email
            $emailData = [
                'retraite' => [
                    'nom_complet' => $retraite->prenoms . ' ' . $retraite->nom,
                    'numero_pension' => $retraite->numero_pension,
                    'email' => $retraite->email,
                    'telephone' => $retraite->telephone,
                    'situation_matrimoniale' => $retraite->situation_matrimoniale ?? 'Non renseignée',
                    'date_retraite' => $retraite->date_retraite?->format('d/m/Y') ?? 'Non renseignée',
                    'montant_pension' => $retraite->montant_pension ? number_format($retraite->montant_pension, 0, ',', ' ') . ' FCFA' : 'Non renseigné'
                ],
                'documents' => collect($fichiersValides)->map(function($item) {
                    $doc = $item['document'];
                    return [
                        'nom_original' => $doc->nom_original,
                        'type' => $doc->type_document === 'certificat_vie' ? 'Certificat de Vie' : 'Autre Document',
                        'taille' => $this->formatFileSize($item['taille']),
                        'date_depot' => $doc->date_depot->format('d/m/Y à H:i'),
                        'date_expiration' => $doc->date_expiration?->format('d/m/Y'),
                        'autorite_emission' => $doc->autorite_emission,
                        'description' => $doc->description
                    ];
                }),
                'statistiques' => [
                    'total_documents' => count($fichiersValides),
                    'certificats_vie' => collect($fichiersValides)->where('document.type_document', 'certificat_vie')->count(),
                    'autres_documents' => collect($fichiersValides)->where('document.type_document', 'autre')->count(),
                    'documents_expires' => 0
                ],
                'timestamp' => now()->format('d/m/Y à H:i:s')
            ];

            // ÉTAPE 3: Envoyer l'email avec pièces jointes
            Mail::send('emails.nouveau_document_retraite', $emailData, function($message) use ($retraite, $fichiersValides) {
                $message->to(config('app.reclamation_email'));
                $message->subject('Nouveau(x) document(s) déposé(s) - ' . $retraite->prenoms . ' ' . $retraite->nom);
                
                Log::info('Ajout des pièces jointes', ['nb_pieces' => count($fichiersValides)]);
                
                // Ajouter chaque pièce jointe validée
                foreach ($fichiersValides as $item) {
                    $doc = $item['document'];
                    $cheminAbsolu = $item['chemin_absolu'];
                    
                    try {
                        $message->attach($cheminAbsolu, [
                            'as' => $doc->nom_original,
                            'mime' => $this->getMimeType($doc->extension)
                        ]);
                        
                        Log::info('Pièce jointe AJOUTÉE', [
                            'document_id' => $doc->id,
                            'nom_fichier' => $doc->nom_original,
                            'taille' => $item['taille']
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('ERREUR ajout pièce jointe', [
                            'document_id' => $doc->id,
                            'nom_fichier' => $doc->nom_original,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            Log::info('Email envoyé avec SUCCÈS', [
                'nb_pieces_jointes' => count($fichiersValides)
            ]);

        } catch (\Exception $e) {
            Log::error('ERREUR CRITIQUE envoi email', [
                'retraite_id' => $retraite->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtenir le type MIME
     */
    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Formater taille fichier
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}