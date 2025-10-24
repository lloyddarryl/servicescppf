<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Models\ReclamationHistorique;
use App\Models\Notification;
use App\Models\MessageDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminReclamationController extends Controller
{
    /**
     * Liste des réclamations avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = Reclamation::query();

            // Filtres
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('priorite')) {
                $query->where('priorite', $request->priorite);
            }

            if ($request->filled('type_reclamation')) {
                $query->where('type_reclamation', $request->type_reclamation);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_reclamation', 'like', "%{$search}%")
                      ->orWhere('user_nom', 'like', "%{$search}%")
                      ->orWhere('user_prenoms', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortField = $request->get('sort_field', 'date_soumission');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $reclamations = $query->paginate($request->get('per_page', 15));

            // Ajouter des infos calculées
            $reclamations->getCollection()->transform(function ($reclamation) {
                // ✅ APRÈS:
// Calculer le temps d'attente formaté ET en jours
            if ($reclamation->statut === 'resolu' || $reclamation->statut === 'ferme' || $reclamation->statut === 'rejete') {
    // Si réclamation traitée, afficher "TRAITÉ" ou temps depuis traitement
            if ($reclamation->date_traitement) {
            $reclamation->temps_attente_format = "TRAITÉ";
            $reclamation->jours_attente = $reclamation->date_traitement->diffInDays($reclamation->date_soumission);
            } else {
            $reclamation->temps_attente_format = "TRAITÉ";
            $reclamation->jours_attente = $reclamation->date_soumission->diffInDays(now());
            }
            } else {
        // Si en attente/cours, calculer depuis date_soumission
            $reclamation->temps_attente_format = $this->formaterTempsAttente($reclamation->date_soumission);
            $reclamation->jours_attente = $reclamation->date_soumission->diffInDays(now());
            }
                $reclamation->urgent = $reclamation->priorite === 'urgente' || 
                                      ($reclamation->statut === 'en_attente' && $reclamation->jours_attente > 3);
                $reclamation->peut_traiter = in_array($reclamation->statut, ['en_attente', 'en_cours', 'en_revision']);
                
                // Infos utilisateur provenant des tables agents ou retraites
                $userInfo = null;
                if ($reclamation->user_type === 'agent') {
                    $userInfo = DB::table('agents')->where('id', $reclamation->user_id)->first();
                    if ($userInfo) {
                        $reclamation->user_info = (object) [
                            'nom' => $userInfo->nom ?? $reclamation->user_nom,
                            'prenoms' => $userInfo->prenoms ?? $reclamation->user_prenoms,
                            'email' => $userInfo->email ?? $reclamation->user_email,
                            'telephone' => $userInfo->telephone ?? $reclamation->user_telephone,
                            'nom_complet' => trim(($userInfo->prenoms ?? $reclamation->user_prenoms) . ' ' . ($userInfo->nom ?? $reclamation->user_nom)),
                            'type' => $reclamation->user_type,
                            // pour les agents actifs le champ matricule s'appelle matricule_solde
                            'matricule_solde' => $userInfo->matricule_solde ?? null,
                            'solde' => $userInfo->solde ?? null
                        ];
                    } else {
                        // fallback si l'agent n'existe pas
                        $reclamation->user_info = (object) [
                            'nom' => $reclamation->user_nom,
                            'prenoms' => $reclamation->user_prenoms,
                            'email' => $reclamation->user_email,
                            'telephone' => $reclamation->user_telephone,
                            'nom_complet' => $reclamation->user_prenoms . ' ' . $reclamation->user_nom,
                            'type' => $reclamation->user_type
                        ];
                    }
                } elseif ($reclamation->user_type === 'retraite') {
                    $userInfo = DB::table('retraites')->where('id', $reclamation->user_id)->first();
                    if ($userInfo) {
                        $reclamation->user_info = (object) [
                            'nom' => $userInfo->nom ?? $reclamation->user_nom,
                            'prenoms' => $userInfo->prenoms ?? $reclamation->user_prenoms,
                            'email' => $userInfo->email ?? $reclamation->user_email,
                            'telephone' => $userInfo->telephone ?? $reclamation->user_telephone,
                            'nom_complet' => trim(($userInfo->prenoms ?? $reclamation->user_prenoms) . ' ' . ($userInfo->nom ?? $reclamation->user_nom)),
                            'type' => $reclamation->user_type,
                            // pour les retraites utiliser numero_pension
                            'numero_pension' => $userInfo->numero_pension ?? null
                        ];
                    } else {
                        // fallback si la retraite n'existe pas
                        $reclamation->user_info = (object) [
                            'nom' => $reclamation->user_nom,
                            'prenoms' => $reclamation->user_prenoms,
                            'email' => $reclamation->user_email,
                            'telephone' => $reclamation->user_telephone,
                            'nom_complet' => $reclamation->user_prenoms . ' ' . $reclamation->user_nom,
                            'type' => $reclamation->user_type
                        ];
                    }
                } else {
                    // type inconnu -> utiliser données présentes sur la réclamation
                    $reclamation->user_info = (object) [
                        'nom' => $reclamation->user_nom,
                        'prenoms' => $reclamation->user_prenoms,
                        'email' => $reclamation->user_email,
                        'telephone' => $reclamation->user_telephone,
                        'nom_complet' => $reclamation->user_prenoms . ' ' . $reclamation->user_nom,
                        'type' => $reclamation->user_type
                    ];
                }
                
                return $reclamation;
            });

            return response()->json([
                'success' => true,
                'data' => $reclamations,
                'filtres_disponibles' => [
                    'statuts' => ['en_attente', 'en_cours', 'en_revision', 'resolu', 'ferme', 'rejete'],
                    'priorites' => ['basse', 'normale', 'haute', 'urgente'],
                    'types' => Reclamation::select('type_reclamation')->distinct()->pluck('type_reclamation')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération réclamations admin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réclamations'
            ], 500);
        }
    }

    /**
 * ✅ MÉTHODE SHOW CORRIGÉE - À COPIER/COLLER COMPLÈTEMENT
 */
public function show($id)
{
    try {
        $reclamation = Reclamation::findOrFail($id);
        
        $reclamation->jours_attente = $reclamation->date_soumission->diffInDays(now());
        $reclamation->peut_traiter = in_array($reclamation->statut, ['en_attente', 'en_cours', 'en_revision']);
        
        // Infos utilisateur
        $reclamation->user_info = (object) [
            'nom' => $reclamation->user_nom,
            'prenoms' => $reclamation->user_prenoms,
            'email' => $reclamation->user_email,
            'telephone' => $reclamation->user_telephone,
            'nom_complet' => $reclamation->user_prenoms . ' ' . $reclamation->user_nom,
            'type' => $reclamation->user_type,
            'matricule_solde' => $reclamation->user_type === 'agent' 
                ? \DB::table('agents')->where('id', $reclamation->user_id)->value('matricule_solde')
                : null,
            'numero_pension' => $reclamation->user_type === 'retraite'
                ? \DB::table('retraites')->where('id', $reclamation->user_id)->value('numero_pension')
                : null
        ];

        // ✅ CORRECTION : Vérifier et formater les documents correctement
        $documents = [];
        
        if ($reclamation->documents) {
            // Décoder les documents
            $documentsData = is_string($reclamation->documents) 
                ? json_decode($reclamation->documents, true) 
                : $reclamation->documents;

            \Log::info('📄 [ADMIN] Documents récupérés:', [
                'reclamation_id' => $reclamation->id,
                'documents_count' => count($documentsData ?? []),
                'format' => is_array($documentsData[0] ?? null) ? 'objet' : 'string'
            ]);

            if (is_array($documentsData)) {
                foreach ($documentsData as $index => $doc) {
                    // ✅ Gérer les DEUX formats
                    if (is_array($doc)) {
                        // FORMAT NOUVEAU (objet avec nom_original, chemin, etc.)
                        $chemin = $doc['chemin'] ?? $doc['path'] ?? $doc['url'] ?? '';
                        $cleanPath = str_replace('public/', '', $chemin);
                        
                        $documents[] = [
                            'nom' => $doc['nom_original'] ?? $doc['nom'] ?? basename($chemin),
                            'taille' => $doc['taille'] ?? 0,
                            'type' => $doc['type'] ?? pathinfo($chemin, PATHINFO_EXTENSION),
                            'chemin' => $chemin,
                            'url' => \Storage::disk('public')->exists($cleanPath) 
                                ? \Storage::disk('public')->url($cleanPath)
                                : \Storage::url($chemin),
                            'date_upload' => $doc['date_upload'] ?? null
                        ];

                        \Log::info("✅ [ADMIN] Document {$index} formaté (objet):", [
                            'nom' => $documents[$index]['nom'],
                            'taille' => $documents[$index]['taille']
                        ]);
                    } 
                    else if (is_string($doc)) {
                        // FORMAT ANCIEN (string simple)
                        $documents[] = [
                            'nom' => basename($doc),
                            'taille' => \Storage::exists($doc) ? \Storage::size($doc) : 0,
                            'type' => pathinfo($doc, PATHINFO_EXTENSION),
                            'chemin' => $doc,
                            'url' => \Storage::url($doc),
                            'date_upload' => null
                        ];

                        \Log::info("✅ [ADMIN] Document {$index} formaté (string):", [
                            'nom' => $documents[$index]['nom'],
                            'chemin' => $doc
                        ]);
                    }
                }
            }
        }
        
        $reclamation->documents_info = $documents;

        \Log::info('📋 [ADMIN] Réponse finale documents_info:', [
            'reclamation_id' => $reclamation->id,
            'documents_count' => count($documents),
            'documents_info' => $documents
        ]);

        return response()->json([
            'success' => true,
            'data' => $reclamation
        ]);

    } catch (\Exception $e) {
        \Log::error('❌ [ADMIN] Erreur récupération réclamation:', [
            'reclamation_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Réclamation non trouvée'
        ], 404);
    }
}

    /**
     * Traiter une réclamation
     */
    public function traiter(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_cours,en_revision,resolu,ferme,rejete',
            'reponse_admin' => 'required|string|max:2000',
            'priorite' => 'nullable|in:basse,normale,haute,urgente'
        ]);

        try {
            DB::beginTransaction();

            $reclamation = Reclamation::findOrFail($id);
            $admin = auth('admin')->user();
            $ancienStatut = $reclamation->statut;

            // Mise à jour
            $reclamation->update([
                'statut' => $request->statut,
                'reponse_admin' => $request->reponse_admin,
                'admin_id' => $admin->id,
                'date_traitement' => now(),
                'date_reponse_admin' => now(),
                'priorite' => $request->priorite ?? $reclamation->priorite
            ]);

            // Enregistrer dans l'historique
    ReclamationHistorique::create([
        'reclamation_id' => $reclamation->id,
        'ancien_statut' => $ancienStatut,
        'nouveau_statut' => $request->statut,
        'commentaire' => $request->reponse_admin,
        'modifie_par' => $admin->nom_complet ?? $admin->nom ?? 'Admin',
        'admin_id' => $admin->id
        ]);

        // Créer notification pour l'utilisateur
    Notification::create([
    'user_id' => $reclamation->user_id,
    'user_type' => $reclamation->user_type,
    'type' => 'reponse_reclamation',
    'titre' => 'Réponse à votre réclamation',
    'message' => "L'administration a répondu à votre réclamation #{$reclamation->numero_reclamation}. Nouveau statut: " . (Reclamation::$statutsLibelles[$request->statut] ?? $request->statut),
    'lien' => "/reclamations",
    'lu' => false
    ]);

            // Enregistrer l'activité
            $admin->enregistrerActivite(
                'traitement_reclamation',
                "Réclamation #{$reclamation->numero_reclamation} : {$ancienStatut} → {$request->statut}",
                $reclamation
            );

            // Envoyer un message à l'utilisateur
            $this->envoyerMessageAutomatique($reclamation, $admin, $request->statut, $request->reponse_admin);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation traitée avec succès',
                'data' => $reclamation->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur traitement réclamation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement'
            ], 500);
        }
    }

/**
 * Récupérer l'historique d'une réclamation
 */
public function historique($id)
{
    try {
        $reclamation = Reclamation::findOrFail($id);
        
        // ⚠️ IMPORTANT: Utiliser ReclamationHistorique SANS S
        $historique = ReclamationHistorique::where('reclamation_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($item) use ($reclamation) {
                return [
                    'id' => $item->id,
                    'ancien_statut' => $item->ancien_statut,
                    'ancien_statut_libelle' => Reclamation::$statutsLibelles[$item->ancien_statut] ?? $item->ancien_statut,
                    'nouveau_statut' => $item->nouveau_statut,
                    'nouveau_statut_libelle' => Reclamation::$statutsLibelles[$item->nouveau_statut] ?? $item->nouveau_statut,
                    'commentaire' => $item->commentaire,
                    'modifie_par' => $item->modifie_par,
                    'date' => $item->created_at->format('d/m/Y à H:i'),
                    'temps_ecoule' => $item->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $historique
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur historique réclamation: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération de l\'historique'
        ], 500);
    }
}

    /**
     * Envoyer un message automatique
     */
    private function envoyerMessageAutomatique($reclamation, $admin, $statut, $reponse)
    {
        if (!$reclamation->user_id) {
            Log::warning('Impossible d\'envoyer un message : user_id manquant pour réclamation #' . $reclamation->id);
            return;
        }

        $messages = [
            'en_cours' => [
                'titre' => 'Réclamation en cours de traitement',
                'type' => 'info',
                'priority' => 'normal'
            ],
            'en_revision' => [
                'titre' => 'Réclamation en révision',
                'type' => 'warning',
                'priority' => 'normal'
            ],
            'resolu' => [
                'titre' => 'Réclamation résolue',
                'type' => 'success',
                'priority' => 'normal'
            ],
            'ferme' => [
                'titre' => 'Réclamation fermée',
                'type' => 'info',
                'priority' => 'normal'
            ],
            'rejete' => [
                'titre' => 'Réclamation rejetée',
                'type' => 'error',
                'priority' => 'high'
            ]
        ];

        if (!isset($messages[$statut])) {
            return;
        }

        $config = $messages[$statut];

        $contenu = "Bonjour {$reclamation->user_prenoms} {$reclamation->user_nom},\n\n";
        $contenu .= "Votre réclamation N° {$reclamation->numero_reclamation} a été mise à jour.\n\n";
        $contenu .= "Nouveau statut : {$statut}\n\n";
        $contenu .= "Réponse de l'administration :\n{$reponse}\n\n";
        $contenu .= "Cordialement,\nAdministration CPPF";

        try {
            MessageDashboard::create([
                'admin_id' => $admin->id,
                'user_type' => $reclamation->user_type,
                'user_id' => $reclamation->user_id,
                'titre' => $config['titre'],
                'message' => $contenu,
                'type' => $config['type'],
                'statut' => 'envoye',
                'priority' => $config['priority']
            ]);
            
            Log::info('Message automatique envoyé pour réclamation #' . $reclamation->numero_reclamation);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi message automatique réclamation : ' . $e->getMessage());
        }
    }

 /**
 * Formater le temps d'attente de manière lisible
 * 
 * @param \Carbon\Carbon $dateSoumission
 * @return string
 */
private function formaterTempsAttente($dateSoumission)
{
    $now = now();
    $diff = $dateSoumission->diff($now);
    
    // Calculer en minutes totales
    $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    if ($totalMinutes < 1) {
        return "À l'instant";
    } elseif ($totalMinutes < 60) {
        // Moins d'une heure → afficher en minutes
        return $totalMinutes . " min";
    } elseif ($totalMinutes < 1440) {
        // Moins de 24h → afficher en heures
        $heures = floor($totalMinutes / 60);
        return $heures . " h";
    } else {
        // Plus de 24h → afficher en jours
        $jours = floor($totalMinutes / 1440);
        return $jours . " jour" . ($jours > 1 ? "s" : "");
    }
}

    /**
     * Statistiques des réclamations
     */
    public function statistiques()
    {
        try {
            $today = now();
            $startOfMonth = $today->copy()->startOfMonth();

            $stats = [
                'globales' => [
                    'total' => Reclamation::count(),
                    'en_attente' => Reclamation::where('statut', 'en_attente')->count(),
                    'en_cours' => Reclamation::where('statut', 'en_cours')->count(),
                    'resolues' => Reclamation::where('statut', 'resolu')->count(),
                    'fermees' => Reclamation::where('statut', 'ferme')->count(),
                    'rejetees' => Reclamation::where('statut', 'rejete')->count(),
                    'urgentes' => Reclamation::where('priorite', 'urgente')
                        ->whereNotIn('statut', ['resolu', 'ferme', 'rejete'])->count()
                ],
                'periode' => [
                    'nouvelles_ce_mois' => Reclamation::where('date_soumission', '>=', $startOfMonth)->count(),
                    'traitees_ce_mois' => Reclamation::whereNotNull('date_traitement')
                        ->where('date_traitement', '>=', $startOfMonth)->count()
                ],
                'par_type' => Reclamation::select('type_reclamation', DB::raw('count(*) as total'))
                    ->groupBy('type_reclamation')->get(),
                'par_priorite' => Reclamation::select('priorite', DB::raw('count(*) as total'))
                    ->groupBy('priorite')->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statistiques réclamations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
 * ✅ Télécharger un document joint (VERSION ADMIN CORRIGÉE)
 */
public function telechargerDocument($id, $index)
{
    try {
        Log::info('📥 [ADMIN] Téléchargement document réclamation:', [
            'reclamation_id' => $id,
            'document_index' => $index,
            'admin_id' => auth('admin')->user()->id
        ]);

        // Récupérer la réclamation
        $reclamation = Reclamation::findOrFail($id);

        // Vérifier que des documents existent
        if (empty($reclamation->documents)) {
            Log::warning('⚠️ Aucun document trouvé pour cette réclamation');
            return response()->json([
                'success' => false,
                'message' => 'Aucun document disponible'
            ], 404);
        }

        // Décoder les documents (peuvent être en JSON ou array)
        $documents = is_string($reclamation->documents) 
            ? json_decode($reclamation->documents, true) 
            : $reclamation->documents;

        Log::info('📄 Documents décodés:', [
            'count' => count($documents),
            'index_demande' => $index,
            'format' => is_array($documents[0] ?? null) ? 'nouveau (objet)' : 'ancien (string)'
        ]);

        // Vérifier que l'index existe
        if (!isset($documents[$index])) {
            Log::warning('⚠️ Index de document invalide:', [
                'index_demande' => $index,
                'documents_count' => count($documents)
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        // ✅ NOUVEAU : Gérer les DEUX formats de stockage
        $documentInfo = $documents[$index];
        $documentPath = null;
        $documentNom = null;

        // FORMAT 1 : Nouveau format (objet avec chemin, nom_original, etc.)
        if (is_array($documentInfo)) {
            // Chercher le chemin dans différentes clés possibles
            $documentPath = $documentInfo['chemin'] 
                         ?? $documentInfo['path'] 
                         ?? $documentInfo['url'] 
                         ?? null;
            
            $documentNom = $documentInfo['nom_original'] 
                        ?? $documentInfo['nom'] 
                        ?? $documentInfo['nom_stocke'] 
                        ?? basename($documentPath ?? '');

            Log::info('📎 [ADMIN] Format nouveau (objet):', [
                'chemin' => $documentPath,
                'nom' => $documentNom,
                'document_info' => $documentInfo
            ]);
        }
        // FORMAT 2 : Ancien format (string simple)
        else if (is_string($documentInfo)) {
            $documentPath = $documentInfo;
            $documentNom = basename($documentPath);

            Log::info('📎 [ADMIN] Format ancien (string):', [
                'chemin' => $documentPath,
                'nom' => $documentNom
            ]);
        }

        // Vérifier qu'on a un chemin valide
        if (!$documentPath) {
            Log::error('❌ [ADMIN] Chemin du document invalide:', [
                'document_info' => $documentInfo
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Chemin du document invalide'
            ], 500);
        }

        // ✅ IMPORTANT : Nettoyer le chemin (enlever "public/" si présent)
        $cleanPath = str_replace('public/', '', $documentPath);
        
        // Essayer d'abord avec le chemin nettoyé sur le disk public
        if (Storage::disk('public')->exists($cleanPath)) {
            $fullPath = storage_path('app/public/' . $cleanPath);
            Log::info('✅ [ADMIN] Fichier trouvé (disk public):', [
                'clean_path' => $cleanPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            // Enregistrer l'activité admin
            $admin = auth('admin')->user();
            $admin->enregistrerActivite(
                'telechargement_document_reclamation',
                "Téléchargement document réclamation #{$reclamation->numero_reclamation}",
                $reclamation
            );

            if (file_exists($fullPath)) {
                return response()->download($fullPath, $documentNom);
            }
        }

        // Sinon essayer avec le chemin original sur le disk par défaut
        if (Storage::exists($documentPath)) {
            $fullPath = storage_path('app/' . $documentPath);
            Log::info('✅ [ADMIN] Fichier trouvé (disk default):', [
                'path' => $documentPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            // Enregistrer l'activité admin
            $admin = auth('admin')->user();
            $admin->enregistrerActivite(
                'telechargement_document_reclamation',
                "Téléchargement document réclamation #{$reclamation->numero_reclamation}",
                $reclamation
            );

            if (file_exists($fullPath)) {
                return response()->download($fullPath, $documentNom);
            }
        }

        // Si rien n'a fonctionné, le fichier n'existe pas
        Log::error('❌ [ADMIN] Fichier introuvable sur le disque:', [
            'original_path' => $documentPath,
            'clean_path' => $cleanPath,
            'checked_paths' => [
                'public' => storage_path('app/public/' . $cleanPath),
                'default' => storage_path('app/' . $documentPath)
            ]
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Fichier introuvable sur le serveur'
        ], 404);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::error('❌ [ADMIN] Réclamation non trouvée');
        return response()->json([
            'success' => false,
            'message' => 'Réclamation non trouvée'
        ], 404);

    } catch (\Exception $e) {
        Log::error('❌ [ADMIN] Erreur lors du téléchargement du document:', [
            'reclamation_id' => $id,
            'document_index' => $index,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
        ], 500);
    }
}

/**
     * Supprimer une réclamation
     */
    public function supprimer($id)
    {
        try {
            DB::beginTransaction();

            $reclamation = Reclamation::findOrFail($id);
            $admin = auth('admin')->user();
            $numeroReclamation = $reclamation->numero_reclamation;

            // Supprimer les documents associés si présents
            if ($reclamation->documents) {
                $documents = is_string($reclamation->documents) 
                    ? json_decode($reclamation->documents, true) 
                    : $reclamation->documents;
                
                if (is_array($documents)) {
                    foreach ($documents as $doc) {
                        // Extraire le chemin selon le format
                        if (is_array($doc)) {
                            $docPath = $doc['path'] ?? ($doc[0] ?? null);
                        } else {
                            $docPath = $doc;
                        }
                        
                        // Supprimer seulement si c'est une string valide
                        if (is_string($docPath) && !empty($docPath)) {
                            try {
                                if (Storage::exists($docPath)) {
                                    Storage::delete($docPath);
                                }
                            } catch (\Exception $e) {
                                \Log::warning("Impossible de supprimer le document: {$docPath}", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }

            // Enregistrer l'activité avant suppression
            $admin->enregistrerActivite(
                'suppression_reclamation',
                "Suppression de la réclamation #{$numeroReclamation} - Type: {$reclamation->type_reclamation}, Statut: {$reclamation->statut}",
                null
            );

            // Supprimer la réclamation
            $reclamation->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression réclamation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

}