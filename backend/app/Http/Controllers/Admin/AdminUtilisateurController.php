<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\RendezVousDemande;
use App\Models\Reclamation;
use App\Models\DocumentRetraite;
use App\Models\MessageDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AdminUtilisateurController extends Controller
{
    /**
     * Liste des agents avec pagination et filtres
     */
    public function indexAgents(Request $request)
    {
        try {
            $query = Agent::query();

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('matricule', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('ministere')) {
                $query->where('ministere', $request->ministere);
            }

            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            // Tri
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $agents = $query->paginate($request->get('per_page', 15));

            // Ajouter des statistiques pour chaque agent
            $agents->getCollection()->transform(function ($agent) {
                $agent->total_rdv = $agent->rendezVous()->count();
                $agent->total_reclamations = $agent->reclamations()->count();
                $agent->derniere_connexion = $agent->derniere_connexion;
                return $agent;
            });

            return response()->json([
                'success' => true,
                'data' => $agents,
                'filtres_disponibles' => [
                    'statuts' => Agent::distinct('statut')->pluck('statut'),
                    'ministeres' => Agent::distinct('ministere')->whereNotNull('ministere')->pluck('ministere')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des agents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des agents'
            ], 500);
        }
    }

    /**
     * Liste des retraités avec pagination et filtres
     */
    public function indexRetraites(Request $request)
    {
        try {
            $query = Retraite::query();

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('matricule', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('derniere_pension_debut') && $request->filled('derniere_pension_fin')) {
                $query->whereBetween('derniere_pension', [$request->derniere_pension_debut, $request->derniere_pension_fin]);
            }

            // Tri
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $retraites = $query->paginate($request->get('per_page', 15));

            // Ajouter des statistiques pour chaque retraité
            $retraites->getCollection()->transform(function ($retraite) {
                $retraite->total_documents = $retraite->documents()->count();
                $retraite->documents_en_attente = $retraite->documents()->where('statut', 'en_attente')->count();
                $retraite->derniere_connexion = $retraite->derniere_connexion;
                return $retraite;
            });

            return response()->json([
                'success' => true,
                'data' => $retraites,
                'filtres_disponibles' => [
                    'statuts' => Retraite::distinct('statut')->pluck('statut')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des retraités: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des retraités'
            ], 500);
        }
    }

    /**
     * Détails d'un agent spécifique
     */
    public function showAgent($id)
    {
        try {
            $agent = Agent::with([
                'rendezVous' => function($query) {
                    $query->orderBy('date_rdv', 'desc')->limit(10);
                },
                'reclamations' => function($query) {
                    $query->orderBy('date_soumission', 'desc')->limit(10);
                }
            ])->findOrFail($id);

            // Statistiques détaillées
            $statistiques = [
                'total_rdv' => $agent->rendezVous()->count(),
                'rdv_confirmes' => $agent->rendezVous()->where('statut', 'confirme')->count(),
                'rdv_en_attente' => $agent->rendezVous()->where('statut', 'en_attente')->count(),
                'total_reclamations' => $agent->reclamations()->count(),
                'reclamations_traitees' => $agent->reclamations()->where('statut', 'traitee')->count(),
                'reclamations_en_cours' => $agent->reclamations()->where('statut', 'en_cours')->count(),
                'messages_recus' => MessageDashboard::where('user_id', $id)->where('user_type', 'agent')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'agent' => $agent,
                    'statistiques' => $statistiques
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'agent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Agent non trouvé'
            ], 404);
        }
    }

    /**
     * Détails d'un retraité spécifique
     */
    public function showRetraite($id)
    {
        try {
            $retraite = Retraite::with([
                'documents' => function($query) {
                    $query->orderBy('date_depot', 'desc')->limit(10);
                }
            ])->findOrFail($id);

            // Statistiques détaillées
            $statistiques = [
                'total_documents' => $retraite->documents()->count(),
                'documents_valides' => $retraite->documents()->where('statut', 'valide')->count(),
                'documents_en_attente' => $retraite->documents()->where('statut', 'en_attente')->count(),
                'documents_rejetes' => $retraite->documents()->where('statut', 'rejete')->count(),
                'messages_recus' => MessageDashboard::where('user_id', $id)->where('user_type', 'retraite')->count(),
                'derniere_pension' => $retraite->derniere_pension,
                'certificat_vie_expire' => $retraite->documents()
                    ->where('type_document', 'certificat_vie')
                    ->where('date_expiration', '<', now())
                    ->exists()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'retraite' => $retraite,
                    'statistiques' => $statistiques
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du retraité: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Retraité non trouvé'
            ], 404);
        }
    }

    /**
     * Modifier le statut d'un agent
     */
    public function changerStatutAgent(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:actif,inactif,suspendu,radie',
            'motif' => 'required_if:statut,suspendu,radie|string|max:500'
        ]);

        try {
            $agent = Agent::findOrFail($id);
            $ancienStatut = $agent->statut;
            
            $agent->update([
                'statut' => $request->statut,
                'motif_changement_statut' => $request->motif
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'changement_statut_agent',
                "Statut de l'agent {$agent->nom_complet} changé de {$ancienStatut} à {$request->statut}"
            );

            // Envoyer une notification à l'agent si nécessaire
            if (in_array($request->statut, ['suspendu', 'radie'])) {
                MessageDashboard::create([
                    'admin_id' => auth('admin')->id(),
                    'user_id' => $agent->id,
                    'user_type' => 'agent',
                    'titre' => 'Changement de statut de votre compte',
                    'message' => "Votre compte a été {$request->statut}. Motif: {$request->motif}",
                    'type_message' => 'alerte',
                    'priorite' => 'haute'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'data' => $agent->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Modifier le statut d'un retraité
     */
    public function changerStatutRetraite(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:actif,inactif,suspendu,decede',
            'motif' => 'required_if:statut,suspendu,decede|string|max:500'
        ]);

        try {
            $retraite = Retraite::findOrFail($id);
            $ancienStatut = $retraite->statut;
            
            $retraite->update([
                'statut' => $request->statut,
                'motif_changement_statut' => $request->motif
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'changement_statut_retraite',
                "Statut du retraité {$retraite->nom_complet} changé de {$ancienStatut} à {$request->statut}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'data' => $retraite->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function reinitialiserMotDePasse(Request $request, $type, $id)
    {
        $request->validate([
            'nouveau_mot_de_passe' => 'required|string|min:8|confirmed'
        ]);

        try {
            if ($type === 'agent') {
                $utilisateur = Agent::findOrFail($id);
            } elseif ($type === 'retraite') {
                $utilisateur = Retraite::findOrFail($id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Type d\'utilisateur invalide'
                ], 400);
            }

            $utilisateur->update([
                'password' => Hash::make($request->nouveau_mot_de_passe),
                'premiere_connexion' => true // Forcer une nouvelle première connexion
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'reinitialisation_mot_de_passe',
                "Mot de passe réinitialisé pour {$type} {$utilisateur->nom_complet}"
            );

            // Envoyer un message à l'utilisateur
            MessageDashboard::create([
                'admin_id' => auth('admin')->id(),
                'user_id' => $utilisateur->id,
                'user_type' => $type,
                'titre' => 'Mot de passe réinitialisé',
                'message' => 'Votre mot de passe a été réinitialisé par un administrateur. Vous devrez vous reconnecter.',
                'type_message' => 'info',
                'priorite' => 'normale'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation'
            ], 500);
        }
    }

    /**
     * Statistiques générales des utilisateurs
     */
    public function statistiquesUtilisateurs()
    {
        try {
            $statistiques = [
                'agents' => [
                    'total' => Agent::count(),
                    'actifs' => Agent::where('statut', 'actif')->count(),
                    'inactifs' => Agent::where('statut', 'inactif')->count(),
                    'suspendus' => Agent::where('statut', 'suspendu')->count(),
                    'radies' => Agent::where('statut', 'radie')->count(),
                    'nouveaux_ce_mois' => Agent::whereMonth('created_at', now()->month)->count()
                ],
                'retraites' => [
                    'total' => Retraite::count(),
                    'actifs' => Retraite::where('statut', 'actif')->count(),
                    'inactifs' => Retraite::where('statut', 'inactif')->count(),
                    'suspendus' => Retraite::where('statut', 'suspendu')->count(),
                    'decedes' => Retraite::where('statut', 'decede')->count(),
                    'nouveaux_ce_mois' => Retraite::whereMonth('created_at', now()->month)->count()
                ],
                'connexions' => [
                    'agents_connectes_aujourdhui' => Agent::whereDate('derniere_connexion', today())->count(),
                    'retraites_connectes_aujourdhui' => Retraite::whereDate('derniere_connexion', today())->count(),
                    'agents_jamais_connectes' => Agent::whereNull('derniere_connexion')->count(),
                    'retraites_jamais_connectes' => Retraite::whereNull('derniere_connexion')->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $statistiques
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}