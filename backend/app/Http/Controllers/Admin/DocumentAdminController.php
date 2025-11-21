<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRetraite;
use App\Models\Retraite;
use App\Models\Admin;
use App\Models\EmailEnvoye;
use App\Mail\RappelCertificatMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class DocumentAdminController extends Controller
{
    /**
     * Logger une action admin
     */
    private function logAction($action, $admin, $details = [])
    {
        $logData = array_merge([
            'action' => $action,
            'admin_id' => $admin->id,
            'admin_nom' => $admin->nom . ' ' . $admin->prenoms,
            'timestamp' => now()->toISOString(),
        ], $details);
        
        Log::info("ACTION ADMIN: {$action}", $logData);
    }

    /**
     * Obtenir le dashboard des documents
     */
    public function dashboard()
    {
        try {
            $admin = auth('admin')->user();
            
            // Statistiques globales
            $stats = [
                'total_documents' => DocumentRetraite::count(),
                'documents_actifs' => DocumentRetraite::where('statut', 'actif')->count(),
                'documents_expires' => DocumentRetraite::where('statut', 'expire')->count(),
                'documents_remplaces' => DocumentRetraite::where('statut', 'remplace')->count(),
                
                // Certificats de vie
                'certificats_total' => DocumentRetraite::where('type_document', 'certificat_vie')->count(),
                'certificats_valides' => DocumentRetraite::where('type_document', 'certificat_vie')
                    ->where('statut', 'actif')
                    ->where('date_expiration', '>', now())
                    ->count(),
                'certificats_expires' => DocumentRetraite::where('type_document', 'certificat_vie')
                    ->where(function($q) {
                        $q->where('statut', 'expire')
                          ->orWhere(function($q2) {
                              $q2->where('date_expiration', '<', now())
                                 ->where('statut', 'actif');
                          });
                    })->count(),
                'certificats_expirant_30j' => DocumentRetraite::where('type_document', 'certificat_vie')
                    ->where('statut', 'actif')
                    ->whereBetween('date_expiration', [now(), now()->addDays(30)])
                    ->count(),
                
                // Retraités sans certificat
                'retraites_sans_certificat' => Retraite::whereDoesntHave('certificatsVie', function($q) {
                    $q->where('statut', 'actif');
                })->count(),
                
                // Ce mois
                'documents_ce_mois' => DocumentRetraite::whereMonth('date_depot', now()->month)
                    ->whereYear('date_depot', now()->year)
                    ->count(),
                'certificats_ce_mois' => DocumentRetraite::where('type_document', 'certificat_vie')
                    ->whereMonth('date_depot', now()->month)
                    ->whereYear('date_depot', now()->year)
                    ->count(),
            ];
            
            // Alertes urgentes
            $alertes = [];
            
            if ($stats['certificats_expires'] > 0) {
                $alertes[] = [
                    'type' => 'danger',
                    'titre' => 'Certificats expirés',
                    'message' => "{$stats['certificats_expires']} certificat(s) de vie expiré(s)",
                    'count' => $stats['certificats_expires'],
                    'action_url' => '/admin/documents?filtre=certificats_expires',
                    'icone' => '❌'
                ];
            }
            
            if ($stats['certificats_expirant_30j'] > 0) {
                $alertes[] = [
                    'type' => 'warning',
                    'titre' => 'Expiration imminente',
                    'message' => "{$stats['certificats_expirant_30j']} certificat(s) expire(nt) dans 30 jours",
                    'count' => $stats['certificats_expirant_30j'],
                    'action_url' => '/admin/documents?filtre=certificats_expirant',
                    'icone' => '⚠️'
                ];
            }
            
            if ($stats['retraites_sans_certificat'] > 0) {
                $alertes[] = [
                    'type' => 'danger',
                    'titre' => 'Certificats manquants',
                    'message' => "{$stats['retraites_sans_certificat']} retraité(s) sans certificat valide",
                    'count' => $stats['retraites_sans_certificat'],
                    'action_url' => '/admin/documents?filtre=certificats_manquants',
                    'icone' => '🚨'
                ];
            }
            
            // Documents récents
            $documents_recents = DocumentRetraite::with('retraite')
                ->orderBy('date_depot', 'desc')
                ->limit(10)
                ->get()
                ->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'nom' => $doc->nom_original,
                        'type' => $doc->nom_type,
                        'retraite' => $doc->retraite->nom_complet_avec_titre,
                        'numero_pension' => $doc->retraite->numero_pension,
                        'date_depot' => $doc->date_depot->format('d/m/Y H:i'),
                        'statut' => $doc->statut,
                        'expire_bientot' => $doc->expire_bientot,
                        'is_expire' => $doc->is_expire,
                    ];
                });
            
            // Répartition par type
            $repartition_types = [
                'certificats_vie' => $stats['certificats_total'],
                'autres_documents' => $stats['total_documents'] - $stats['certificats_total'],
            ];
            
            // Évolution ce mois
            $evolution_mois = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $evolution_mois[] = [
                    'date' => $date->format('d/m'),
                    'count' => DocumentRetraite::whereDate('date_depot', $date)->count()
                ];
            }
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'alertes' => $alertes,
                'documents_recents' => $documents_recents,
                'repartition_types' => $repartition_types,
                'evolution_mois' => $evolution_mois,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dashboard documents admin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }
    
    /**
     * Liste des documents avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = DocumentRetraite::with(['retraite']);
            
            Log::info("📋 Liste documents - Query params", [
                'type' => $request->get('type'),
                'statut' => $request->get('statut'),
                'recherche' => $request->get('recherche'),
                'filtre' => $request->get('filtre')
            ]);

            // Filtre par type
            if ($request->has('type') && $request->type !== 'tous') {
                $query->where('type_document', $request->type);
            }
            
            // Filtre par statut
            if ($request->has('statut') && $request->statut !== 'tous') {
                if ($request->statut === 'expire') {
                    $query->where(function($q) {
                        $q->where('statut', 'expire')
                          ->orWhere(function($q2) {
                              $q2->where('date_expiration', '<', now())
                                 ->where('statut', 'actif');
                          });
                    });
                } else {
                    $query->where('statut', $request->statut);
                }
            }
            
            // Recherche
            if ($request->has('recherche') && $request->recherche) {
                $search = $request->recherche;
                $query->where(function($q) use ($search) {
                    $q->where('nom_original', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('autorite_emission', 'LIKE', "%{$search}%")
                      ->orWhereHas('retraite', function($q2) use ($search) {
                          $q2->where('nom', 'LIKE', "%{$search}%")
                             ->orWhere('prenoms', 'LIKE', "%{$search}%")
                             ->orWhere('numero_pension', 'LIKE', "%{$search}%");
                      });
                });
            }
            
            // Filtres prédéfinis
            if ($request->has('filtre') && $request->filtre) {
                switch($request->filtre) {
                    case 'certificats_expires':
                        $query->where('type_document', 'certificat_vie')
                             ->where(function($q) {
                                 $q->where('statut', 'expire')
                                   ->orWhere(function($q2) {
                                       $q2->where('date_expiration', '<', now())
                                          ->where('statut', 'actif');
                                   });
                             });
                        break;
                    
                    case 'certificats_expirant':
                        $query->where('type_document', 'certificat_vie')
                             ->where('statut', 'actif')
                             ->whereBetween('date_expiration', [now(), now()->addDays(30)]);
                        break;
                    
                    case 'certificats_manquants':
                        // Retourner les retraités sans certificat
                        $retraites = Retraite::whereDoesntHave('certificatsVie', function($q) {
                                $q->where('statut', 'actif');
                            })
                            ->orderBy('nom')
                            ->get()
                            ->map(function($retraite) {
                                return [
                                    'id' => $retraite->id,
                                    'nom_complet' => $retraite->prenoms . ' ' . $retraite->nom,
                                    'numero_pension' => $retraite->numero_pension,
                                    'email' => $retraite->email,
                                    'telephone' => $retraite->telephone,
                                    'date_retraite' => $retraite->date_retraite?->format('d/m/Y')
                                ];
                            });
                        
                        return response()->json([
                            'success' => true,
                            'type' => 'retraites_sans_certificat',
                            'data' => $retraites,
                            'total' => $retraites->count()
                        ]);
                }
            }
            
            // Tri
            $sortField = $request->get('sort', 'date_depot');
            $sortOrder = $request->get('order', 'desc');
            
            if ($sortField === 'retraite') {
                $query->join('retraites', 'documents_retraites.retraite_id', '=', 'retraites.id')
                     ->orderBy('retraites.nom', $sortOrder)
                     ->select('documents_retraites.*');
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $documents = $query->paginate($perPage);
            
            // Formater les résultats
            $formattedDocuments = $documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'nom_original' => $doc->nom_original,
                    'nom_fichier' => $doc->nom_fichier,
                    'type_document' => $doc->type_document,
                    'nom_type' => $doc->nom_type,
                    'icone_type' => $doc->icone_type,
                    'extension' => $doc->extension,
                    'taille_formatee' => $doc->taille_formatee,
                    'statut' => $doc->statut,
                    'description' => $doc->description,
                    'date_depot' => $doc->date_depot->format('d/m/Y H:i'),
                    'date_emission' => $doc->date_emission?->format('d/m/Y'),
                    'date_expiration' => $doc->date_expiration?->format('d/m/Y'),
                    'autorite_emission' => $doc->autorite_emission,
                    'is_expire' => $doc->is_expire,
                    'expire_bientot' => $doc->expire_bientot,
                    'jours_avant_expiration' => $doc->jours_avant_expiration,
                    'retraite' => [
                        'id' => $doc->retraite->id,
                        'nom_complet' => $doc->retraite->nom_complet_avec_titre,
                        'numero_pension' => $doc->retraite->numero_pension,
                        'email' => $doc->retraite->email,
                        'telephone' => $doc->retraite->telephone,
                    ],
                    'metadata' => $doc->metadata
                ];
            });
            
            return response()->json([
                'success' => true,
                'type' => 'documents',
                'documents' => $formattedDocuments,
                'pagination' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                    'from' => $documents->firstItem(),
                    'to' => $documents->lastItem()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste documents admin: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des documents'
            ], 500);
        }
    }
    
    /**
     * Obtenir un document spécifique
     */
    public function show($id)
    {
        try {
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'document' => [
                    'id' => $document->id,
                    'nom_original' => $document->nom_original,
                    'nom_fichier' => $document->nom_fichier,
                    'chemin_fichier' => $document->chemin_fichier,
                    'type_document' => $document->type_document,
                    'nom_type' => $document->nom_type,
                    'icone_type' => $document->icone_type,
                    'extension' => $document->extension,
                    'taille_fichier' => $document->taille_fichier,
                    'taille_formatee' => $document->taille_formatee,
                    'statut' => $document->statut,
                    'description' => $document->description,
                    'date_depot' => $document->date_depot->format('d/m/Y H:i'),
                    'date_emission' => $document->date_emission?->format('d/m/Y'),
                    'date_expiration' => $document->date_expiration?->format('d/m/Y'),
                    'autorite_emission' => $document->autorite_emission,
                    'is_expire' => $document->is_expire,
                    'expire_bientot' => $document->expire_bientot,
                    'jours_avant_expiration' => $document->jours_avant_expiration,
                    'retraite' => [
                        'id' => $document->retraite->id,
                        'nom_complet' => $document->retraite->nom_complet_avec_titre,
                        'numero_pension' => $document->retraite->numero_pension,
                        'email' => $document->retraite->email,
                        'telephone' => $document->retraite->telephone,
                        'date_naissance' => $document->retraite->date_naissance?->format('d/m/Y'),
                        'date_retraite' => $document->retraite->date_retraite?->format('d/m/Y'),
                        'situation_matrimoniale' => $document->retraite->situation_matrimoniale,
                    ],
                    'metadata' => $document->metadata,
                    'fichier_existe' => $document->fichierExiste()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur détails document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable'
            ], 404);
        }
    }
    
    /**
 * ✅ CORRECTION: Méthode view() pour DocumentAdminController
 * À ajouter dans app/Http/Controllers/Admin/DocumentAdminController.php
 */

public function view($id)
{
    try {
        // ✅ CORRECTION CRITIQUE: Accepter le token depuis l'URL pour les iframes
        // Les iframes ne peuvent pas envoyer les headers Authorization
        if (request()->has('token') && !auth('admin')->check()) {
            $token = request()->get('token');
            
            // Trouver le token dans la table personal_access_tokens
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if ($accessToken && $accessToken->tokenable instanceof \App\Models\Admin) {
                // Authentifier l'admin
                auth('admin')->setUser($accessToken->tokenable);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ], 401);
            }
        }
        
        // Vérifier que l'admin est bien authentifié
        if (!auth('admin')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        $document = DocumentRetraite::findOrFail($id);
        
        // Vérifier que le fichier existe
        $cheminAbsolu = storage_path('app/' . $document->chemin_fichier);
        
        Log::info("📄 Visualisation document", [
            'document_id' => $document->id,
            'nom_original' => $document->nom_original,
            'chemin_bdd' => $document->chemin_fichier,
            'chemin_absolu' => $cheminAbsolu,
            'file_exists' => file_exists($cheminAbsolu),
            'file_size' => file_exists($cheminAbsolu) ? filesize($cheminAbsolu) : 0,
            'readable' => file_exists($cheminAbsolu) && is_readable($cheminAbsolu)
        ]);
        
        if (!file_exists($cheminAbsolu)) {
            Log::error("Erreur visualisation: Fichier non trouvé", [
                'chemin' => $cheminAbsolu
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé sur le serveur'
            ], 404);
        }
        
        if (!is_readable($cheminAbsolu)) {
            Log::error("Erreur visualisation: Fichier non lisible", [
                'chemin' => $cheminAbsolu
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fichier non accessible'
            ], 403);
        }
        
        // Déterminer le type MIME
        $mimeType = $this->getMimeType($document->extension);
        
        Log::info("✅ Fichier trouvé et lisible", [
            'taille' => filesize($cheminAbsolu),
            'mime_type' => $mimeType
        ]);
        
        // Retourner le fichier
        return response()->file($cheminAbsolu, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->nom_original . '"'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur visualisation document admin: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la visualisation: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Télécharger un document
     */
    public function download($id)
    {
        try {
            $document = DocumentRetraite::findOrFail($id);
            
            $cheminAbsolu = storage_path('app/' . $document->chemin_fichier);
            
            Log::info("📥 Téléchargement document", [
                'document_id' => $document->id,
                'nom_original' => $document->nom_original,
                'chemin' => $cheminAbsolu,
                'file_exists' => file_exists($cheminAbsolu)
            ]);
            
            if (!file_exists($cheminAbsolu)) {
                Log::error("Erreur téléchargement: Fichier non trouvé");
                
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }
            
            // Retourner le fichier en téléchargement
            return response()->download($cheminAbsolu, $document->nom_original, [
                'Content-Type' => $this->getMimeType($document->extension)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur téléchargement document admin: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement'
            ], 500);
        }
    }
    
    /**
     * Obtenir les motifs de rejet prédéfinis
     */
    public function getMotifsRejet()
    {
        return response()->json([
            'success' => true,
            'motifs' => [
                [
                    'value' => 'illisible',
                    'label' => 'Document illisible ou de mauvaise qualité'
                ],
                [
                    'value' => 'flou',
                    'label' => 'Document flou ou photo de mauvaise résolution'
                ],
                [
                    'value' => 'expire',
                    'label' => 'Document expiré'
                ],
                [
                    'value' => 'incomplet',
                    'label' => 'Document incomplet ou informations manquantes'
                ],
                [
                    'value' => 'mauvais_format',
                    'label' => 'Mauvais format de document'
                ],
                [
                    'value' => 'non_conforme',
                    'label' => 'Document non conforme aux exigences'
                ],
                [
                    'value' => 'falsifie',
                    'label' => 'Suspicion de document falsifié'
                ],
                [
                    'value' => 'mauvais_type',
                    'label' => 'Type de document incorrect'
                ],
                [
                    'value' => 'autre',
                    'label' => 'Autre raison (précisez dans le commentaire)'
                ]
            ]
        ]);
    }
    
    /**
     * Valider un document
     */
    public function valider(Request $request, $id)
    {
        try {
            $admin = auth('admin')->user();
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            $request->validate([
                'commentaire' => 'nullable|string|max:500',
                'envoyer_notification' => 'boolean'
            ]);
            
            // Mettre à jour metadata
            $metadata = $document->metadata ?? [];
            $metadata['validation'] = [
                'admin_id' => $admin->id,
                'admin_nom' => $admin->nom . ' ' . $admin->prenoms,
                'date' => now()->toISOString(),
                'commentaire' => $request->commentaire,
            ];
            
            $document->update(['metadata' => $metadata]);
            
            // Enregistrer l'action
            $this->logAction('validation_document', $admin, [
                'document_id' => $document->id,
                'document_nom' => $document->nom_original,
                'retraite_id' => $document->retraite->id,
                'retraite_nom' => $document->retraite->nom . ' ' . $document->retraite->prenoms,
                'commentaire' => $request->commentaire
            ]);
            
            // TODO: Envoyer notification email si demandé
            if ($request->get('envoyer_notification', false)) {
                Log::info("Notification validation à envoyer à: " . $document->retraite->email);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Document validé avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur validation document: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Rejeter un document
     */
    public function rejeter(Request $request, $id)
    {
        try {
            $admin = auth('admin')->user();
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            $request->validate([
                'motif' => 'required|string',
                'commentaire' => 'nullable|string|max:500',
                'envoyer_notification' => 'boolean'
            ]);
            
            $motifs_disponibles = [
                'illisible' => 'Document illisible ou de mauvaise qualité',
                'flou' => 'Document flou ou photo de mauvaise résolution',
                'expire' => 'Document expiré',
                'incomplet' => 'Document incomplet ou informations manquantes',
                'mauvais_format' => 'Mauvais format de document',
                'non_conforme' => 'Document non conforme aux exigences',
                'falsifie' => 'Suspicion de document falsifié',
                'mauvais_type' => 'Type de document incorrect',
                'autre' => 'Autre raison (voir commentaire)'
            ];
            
            $motif_libelle = $motifs_disponibles[$request->motif] ?? $request->motif;
            
            // Mettre à jour metadata
            $metadata = $document->metadata ?? [];
            $metadata['rejet'] = [
                'admin_id' => $admin->id,
                'admin_nom' => $admin->nom . ' ' . $admin->prenoms,
                'date' => now()->toISOString(),
                'motif' => $request->motif,
                'motif_libelle' => $motif_libelle,
                'commentaire' => $request->commentaire,
            ];
            
            $document->update(['metadata' => $metadata]);
            
            // Enregistrer l'action
            $this->logAction('rejet_document', $admin, [
                'document_id' => $document->id,
                'document_nom' => $document->nom_original,
                'retraite_id' => $document->retraite->id,
                'retraite_nom' => $document->retraite->nom . ' ' . $document->retraite->prenoms,
                'motif' => $motif_libelle,
                'commentaire' => $request->commentaire
            ]);
            
            // TODO: Envoyer notification email si demandé
            if ($request->get('envoyer_notification', false)) {
                Log::info("Notification rejet à envoyer à: " . $document->retraite->email);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Document rejeté'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur rejet document: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprimer un document
     */
    public function destroy($id)
    {
        try {
            $admin = auth('admin')->user();
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            $nom = $document->nom_original;
            $retraite_nom = $document->retraite->nom . ' ' . $document->retraite->prenoms;
            $retraite_id = $document->retraite->id;
            
            // Supprimer le fichier physique
            if (Storage::exists($document->chemin_fichier)) {
                Storage::delete($document->chemin_fichier);
            }
            
            $document->delete();
            
            // Enregistrer l'action
            $this->logAction('suppression_document', $admin, [
                'document_nom' => $nom,
                'retraite_id' => $retraite_id,
                'retraite_nom' => $retraite_nom
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Document supprimé'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur suppression document: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Envoyer un rappel certificat à un retraité
     */
    public function envoyerRappel(Request $request, $retraiteId)
    {
        try {
            $admin = auth('admin')->user();
            $retraite = Retraite::findOrFail($retraiteId);
            
            if (!$retraite->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce retraité n\'a pas d\'adresse email'
                ], 400);
            }
            
            // Envoyer l'email
            try {
                Mail::to($retraite->email)->send(new RappelCertificatMail($retraite));
                Log::info("✅ Rappel certificat envoyé à: " . $retraite->email);
                
                // Enregistrer dans la table emails_envoyes
                EmailEnvoye::create([
                    'retraite_id' => $retraite->id,
                    'type' => 'rappel_certificat',
                    'destinataire' => $retraite->email,
                    'envoye_le' => now()
                ]);
                
            } catch (\Exception $mailError) {
                Log::error("❌ Erreur envoi email: " . $mailError->getMessage());
                throw new \Exception("Erreur lors de l'envoi de l'email: " . $mailError->getMessage());
            }
            
            // Enregistrer l'action
            $this->logAction('rappel_certificat_envoye', $admin, [
                'retraite_id' => $retraite->id,
                'retraite_nom' => $retraite->nom . ' ' . $retraite->prenoms,
                'email' => $retraite->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rappel envoyé avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi rappel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
    /**
     * Obtenir le type MIME selon l'extension
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
}