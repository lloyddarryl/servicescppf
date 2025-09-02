<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            // 🔍 Debug: Vérifier l'utilisateur authentifié
            Log::info('DocumentController::index - Début', [
                'user_id' => $request->user()?->id,
                'user_class' => get_class($request->user()),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_headers' => $request->headers->all()
            ]);

            $retraite = $request->user();
            
            // Vérifier que l'utilisateur est bien un retraité
            if (!$retraite) {
                Log::error('DocumentController::index - Utilisateur non authentifié');
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                    'error_code' => 'AUTH_REQUIRED'
                ], 401);
            }

            if (!($retraite instanceof \App\Models\Retraite)) {
                Log::error('DocumentController::index - Type utilisateur incorrect', [
                    'user_class' => get_class($retraite),
                    'expected' => 'App\Models\Retraite'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités',
                    'error_code' => 'ACCESS_DENIED'
                ], 403);
            }

            Log::info('DocumentController::index - Utilisateur validé', [
                'retraite_id' => $retraite->id,
                'numero_pension' => $retraite->numero_pension,
                'nom_complet' => $retraite->nom . ' ' . $retraite->prenoms
            ]);

            // Récupérer tous les documents avec relations
            $documentsQuery = $retraite->documentsActifs()
                                     ->orderBy('date_depot', 'desc');
            
            Log::info('DocumentController::index - Query documents', [
                'query_sql' => $documentsQuery->toSql(),
                'bindings' => $documentsQuery->getBindings()
            ]);

            $documents = $documentsQuery->get()
                                       ->map(function ($document) {
                                        $joursRestants = $document->jours_avant_expiration; // Utiliser la méthode du modèle
                                           return [
                                               'id' => $document->id,
                                               'nom_original' => $document->nom_original,
                                               'type_document' => $document->type_document,
                                               'nom_type' => $document->nom_type,
                                               'icone_type' => $document->icone_type,
                                               'description' => $document->description,
                                               'taille_formatee' => $document->taille_formatee,
                                               'extension' => $document->extension,
                                               'statut' => $document->statut,
                                               'date_depot' => $document->date_depot->format('d/m/Y H:i'),
                                               'date_emission' => $document->date_emission?->format('d/m/Y'),
                                               'date_expiration' => $document->date_expiration?->format('d/m/Y'),
                                               'autorite_emission' => $document->autorite_emission,
                                               'is_expire' => $document->is_expire,
                                               'expire_bientot' => $document->expire_bientot,
                                               'jours_avant_expiration' => $document->jours_avant_expiration,
                                               'url_telechargement' => route('retraites.documents.download', $document->id),
                                               'peut_remplacer' => $document->type_document === 'certificat_vie',
                                               'statut_expiration' => $joursRestants > 0 ? "Valide encore {$joursRestants} jour(s)" : 'Expiré'

                                           ];
                                       });

            Log::info('DocumentController::index - Documents récupérés', [
                'count' => $documents->count()
            ]);

            // Obtenir les notifications de certificat
            $notifications = $retraite->notifications_certificat ?? [];
            
            // Statistiques
            $statistiques = $retraite->statistiques_documents ?? [
                'total_documents' => 0,
                'certificats_vie' => 0,
                'autres_documents' => 0,
                'documents_expires' => 0
            ];

            Log::info('DocumentController::index - Données finales', [
                'documents_count' => count($documents),
                'notifications_count' => count($notifications),
                'statistiques' => $statistiques
            ]);

            return response()->json([
                'success' => true,
                'retraite' => [
                    'id' => $retraite->id,
                    'nom_complet_avec_titre' => $retraite->nom_complet_avec_titre ?? ($retraite->prenoms . ' ' . $retraite->nom),
                    'numero_pension' => $retraite->numero_pension,
                    'titre_civilite' => $retraite->titre_civilite ?? '',
                ],
                'documents' => $documents,
                'notifications' => $notifications,
                'statistiques' => $statistiques,
                'limites' => [
                    'max_fichiers' => 3,
                    'taille_max_mo' => 5,
                    'extensions_autorisees' => DocumentRetraite::$extensionsAutorisees ?? ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
                    'types_documents' => DocumentRetraite::$typesDocuments ?? []
                ],
                'debug' => [
                    'timestamp' => now()->toISOString(),
                    'user_id' => $retraite->id,
                    'user_type' => get_class($retraite)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DocumentController::index - Exception', [
                'user_id' => $request->user()?->id ?? 'UNKNOWN',
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents',
                'error_code' => 'INTERNAL_ERROR',
                'debug' => [
                    'error_message' => $e->getMessage(),
                    'error_file' => basename($e->getFile()),
                    'error_line' => $e->getLine(),
                    'timestamp' => now()->toISOString()
                ]
            ], 500);
        }
    }

    /**
     * Déposer de nouveaux documents (jusqu'à 3 à la fois)
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'documents' => 'required|array|max:3',
            'documents.*' => 'required|file|max:5120', // 5MB max
            'types' => 'required|array',
            'types.*' => 'required|in:certificat_vie,autre',
            'descriptions' => 'array',
            'descriptions.*' => 'nullable|string|max:255',
            'dates_emission' => 'array',
            'dates_emission.*' => 'nullable|date',
            'autorites_emission' => 'array',
            'autorites_emission.*' => 'nullable|string|max:255'
        ]);

        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }

            $documentsCreated = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->file('documents') as $index => $file) {
                // Validation du fichier
                $validation = DocumentRetraite::validerFichier($file);
                
                if (!$validation['valid']) {
                    $errors[] = [
                        'fichier' => $file->getClientOriginalName(),
                        'erreurs' => $validation['errors']
                    ];
                    continue;
                }

                $type = $request->types[$index];
                $description = $request->descriptions[$index] ?? null;
                $dateEmission = $request->dates_emission[$index] ?? null;
                $autoriteEmission = $request->autorites_emission[$index] ?? null;

                // Validation spécifique selon le type
                if ($type === 'certificat_vie') {
                    if (!$dateEmission) {
                        $errors[] = [
                            'fichier' => $file->getClientOriginalName(),
                            'erreurs' => ['Date d\'émission requise pour un certificat de vie']
                        ];
                        continue;
                    }
                    if (!$autoriteEmission) {
                        $errors[] = [
                            'fichier' => $file->getClientOriginalName(),
                            'erreurs' => ['Autorité d\'émission requise pour un certificat de vie']
                        ];
                        continue;
                    }
                } elseif ($type === 'autre' && !$description) {
                    $errors[] = [
                        'fichier' => $file->getClientOriginalName(),
                        'erreurs' => ['Description requise pour un document de type "Autre"']
                    ];
                    continue;
                }

                // Générer un nom unique pour le fichier
                $nomFichier = $this->genererNomFichier($file, $retraite->id);
                
                // Stocker le fichier
                $cheminFichier = $file->storeAs('documents/retraites/' . $retraite->id, $nomFichier);
                
                if (!$cheminFichier) {
                    $errors[] = [
                        'fichier' => $file->getClientOriginalName(),
                        'erreurs' => ['Erreur lors du stockage du fichier']
                    ];
                    continue;
                }

                // Calculer la date d'expiration pour les certificats de vie
                $dateExpiration = null;
                if ($type === 'certificat_vie' && $dateEmission) {
                    $dateExpiration = DocumentRetraite::calculerDateExpiration($dateEmission);
                }

                // Créer l'enregistrement en base
                $document = DocumentRetraite::create([
                    'retraite_id' => $retraite->id,
                    'nom_original' => $file->getClientOriginalName(),
                    'nom_fichier' => $nomFichier,
                    'chemin_fichier' => $cheminFichier,
                    'type_document' => $type,
                    'description' => $description,
                    'taille_fichier' => $file->getSize(),
                    'extension' => strtolower($file->getClientOriginalExtension()),
                    'date_emission' => $dateEmission,
                    'date_expiration' => $dateExpiration,
                    'autorite_emission' => $autoriteEmission,
                    'date_depot' => now(),
                    'statut' => 'actif',
                    'metadata' => [
                        'mime_type' => $file->getMimeType(),
                        'ip_depot' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                ]);

                $documentsCreated[] = $document;
            }

            // Si tous les fichiers ont échoué
            if (empty($documentsCreated) && !empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun document n\'a pu être traité',
                    'errors' => $errors
                ], 400);
            }

            DB::commit();

            // Envoyer l'email de notification
            $this->envoyerNotificationEmail($retraite, collect($documentsCreated));

            return response()->json([
                'success' => true,
                'message' => count($documentsCreated) === 1 
                    ? 'Document déposé avec succès' 
                    : count($documentsCreated) . ' documents déposés avec succès',
                'documents' => collect($documentsCreated)->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'nom_original' => $doc->nom_original,
                        'type_document' => $doc->type_document,
                        'nom_type' => $doc->nom_type ?? $doc->type_document,
                        'date_depot' => $doc->date_depot->format('d/m/Y H:i')
                    ];
                }),
                'errors' => $errors // Erreurs partielles s'il y en a
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors du dépôt de documents:', [
                'retraite_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du dépôt des documents'
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
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }
            
            $document = DocumentRetraite::where('id', $id)
                                      ->where('retraite_id', $retraite->id)
                                      ->where('statut', 'actif')
                                      ->firstOrFail();

            if (!$document->fichierExiste()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier introuvable sur le serveur'
                ], 404);
            }

            return Storage::download($document->chemin_fichier, $document->nom_original);

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement:', [
                'document_id' => $id,
                'retraite_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement'
            ], 500);
        }
    }

    /**
     * Supprimer un document
     */
    public function destroy(Request $request, $id)
    {
        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }
            
            $document = DocumentRetraite::where('id', $id)
                                      ->where('retraite_id', $retraite->id)
                                      ->where('statut', 'actif')
                                      ->firstOrFail();

            // Les certificats de vie actifs ne peuvent pas être supprimés, seulement remplacés
            if ($document->type_document === 'certificat_vie' && !$document->is_expire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un certificat de vie valide. Veuillez le remplacer.'
                ], 400);
            }

            $document->update(['statut' => 'supprime']);
            $document->supprimerFichier();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression:', [
                'document_id' => $id,
                'retraite_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Obtenir les notifications pour le dashboard
     */
    public function getNotifications(Request $request)
    {
        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }

            $notifications = $retraite->notifications_certificat ?? [];

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notifications:', [
                'retraite_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'notifications' => []
            ]);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function dismissNotification(Request $request)
    {
        $request->validate([
            'type' => 'required|string'
        ]);

        try {
            $retraite = $request->user();
            
            if (!($retraite instanceof \App\Models\Retraite)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux retraités'
                ], 403);
            }

            // Pour l'instant, on stocke ça en session ou cache
            $key = 'notification_dismissed_' . $retraite->id . '_' . $request->type;
            cache()->put($key, true, now()->addHours(24));

            return response()->json([
                'success' => true,
                'message' => 'Notification masquée'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dismiss notification:', [
                'retraite_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du masquage de la notification'
            ], 500);
        }
    }

    /**
     * Générer un nom unique pour le fichier
     */
    private function genererNomFichier($file, $retraiteId)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "retraite_{$retraiteId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Envoyer l'email de notification
     */
    private function envoyerNotificationEmail($retraite, $documents)
    {
        try {
            if (!config('app.reclamation_email')) {
                Log::warning('Email de notification non configuré');
                return;
            }

            $emailData = [
                'retraite' => [
                    'nom_complet' => $retraite->nom_complet_avec_titre ?? ($retraite->prenoms . ' ' . $retraite->nom),
                    'numero_pension' => $retraite->numero_pension,
                    'email' => $retraite->email,
                    'telephone' => $retraite->telephone,
                    'situation_matrimoniale' => $retraite->situation_matrimoniale ?? 'Non renseignée',
                    'date_retraite' => $retraite->date_retraite?->format('d/m/Y') ?? 'Non renseignée',
                    'montant_pension' => $retraite->montant_pension ? number_format($retraite->montant_pension, 0, ',', ' ') . ' FCFA' : 'Non renseigné'
                ],
                'documents' => $documents->map(function($doc) {
                    return [
                        'nom_original' => $doc->nom_original,
                        'type' => $doc->nom_type ?? $doc->type_document,
                        'taille' => $doc->taille_formatee,
                        'date_depot' => $doc->date_depot->format('d/m/Y à H:i'),
                        'date_expiration' => $doc->date_expiration?->format('d/m/Y'),
                        'autorite_emission' => $doc->autorite_emission,
                        'description' => $doc->description
                    ];
                }),
                'statistiques' => $retraite->statistiques_documents ?? [],
                'timestamp' => now()->format('d/m/Y à H:i:s')
            ];

            // Envoyer l'email si le template existe
            if (view()->exists('emails.nouveau_document_retraite')) {
                Mail::send('emails.nouveau_document_retraite', $emailData, function($message) use ($retraite, $documents) {
                    $message->to(config('app.reclamation_email'));
                    $message->subject('Nouveau(x) document(s) déposé(s) - ' . ($retraite->nom_complet_avec_titre ?? $retraite->prenoms . ' ' . $retraite->nom));
                });

                // Marquer comme notifié
                foreach ($documents as $document) {
                    $document->update(['notifie_par_email' => true]);
                }
            } else {
                Log::warning('Template email nouveau_document_retraite introuvable');
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi email notification document:', [
                'retraite_id' => $retraite->id,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer la création du document si l'email échoue
        }
    }
}