<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageDashboard;
use App\Models\Agent;
use App\Models\Retraite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MessageDashboardController extends Controller
{
    /**
     * Envoyer un message à un ou plusieurs utilisateurs
     */
    public function envoyerMessage(Request $request)
    {
        $request->validate([
            'destinataires' => 'required|array|min:1',
            'destinataires.*.user_id' => 'required|integer',
            'destinataires.*.user_type' => 'required|in:agent,retraite',
            'titre' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type_message' => 'required|in:info,alerte,urgent,notification',
            'priorite' => 'required|in:normale,haute,urgente',
            'expire_le' => 'nullable|date|after:today'
        ]);

        try {
            $messagesEnvoyes = 0;
            $erreurs = [];

            DB::beginTransaction();

            foreach ($request->destinataires as $destinataire) {
                try {
                    // Vérifier que l'utilisateur existe
                    if ($destinataire['user_type'] === 'agent') {
                        $utilisateur = Agent::find($destinataire['user_id']);
                    } else {
                        $utilisateur = Retraite::find($destinataire['user_id']);
                    }

                    if (!$utilisateur) {
                        $erreurs[] = "Utilisateur {$destinataire['user_type']} #{$destinataire['user_id']} non trouvé";
                        continue;
                    }

                    // Créer le message
                    MessageDashboard::create([
                        'admin_id' => auth('admin')->id(),
                        'user_id' => $destinataire['user_id'],
                        'user_type' => $destinataire['user_type'],
                        'titre' => $request->titre,
                        'message' => $request->message,
                        'type_message' => $request->type_message,
                        'priorite' => $request->priorite,
                        'expire_le' => $request->expire_le,
                        'statut' => 'non_lu'
                    ]);

                    $messagesEnvoyes++;

                } catch (\Exception $e) {
                    $erreurs[] = "Erreur pour {$destinataire['user_type']} #{$destinataire['user_id']}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'envoi_messages',
                "Messages envoyés à {$messagesEnvoyes} utilisateur(s). Titre: {$request->titre}"
            );

            return response()->json([
                'success' => true,
                'message' => "Messages envoyés avec succès à {$messagesEnvoyes} destinataire(s)",
                'data' => [
                    'messages_envoyes' => $messagesEnvoyes,
                    'erreurs' => $erreurs
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'envoi des messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des messages'
            ], 500);
        }
    }

    /**
     * Envoyer un message à tous les utilisateurs d'un type
     */
    public function envoyerMessageGlobal(Request $request)
    {
        $request->validate([
            'destinataires_type' => 'required|in:agents,retraites,tous',
            'filtres' => 'nullable|array',
            'titre' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type_message' => 'required|in:info,alerte,urgent,notification',
            'priorite' => 'required|in:normale,haute,urgente',
            'expire_le' => 'nullable|date|after:today'
        ]);

        try {
            $destinataires = [];

            // Récupérer les destinataires selon le type
            if (in_array($request->destinataires_type, ['agents', 'tous'])) {
                $queryAgents = Agent::where('statut', 'actif');
                
                // Appliquer les filtres si fournis
                if ($request->filled('filtres.ministere')) {
                    $queryAgents->where('ministere', $request->filtres['ministere']);
                }
                
                $agents = $queryAgents->get();
                foreach ($agents as $agent) {
                    $destinataires[] = [
                        'user_id' => $agent->id,
                        'user_type' => 'agent'
                    ];
                }
            }

            if (in_array($request->destinataires_type, ['retraites', 'tous'])) {
                $queryRetraites = Retraite::where('statut', 'actif');
                
                $retraites = $queryRetraites->get();
                foreach ($retraites as $retraite) {
                    $destinataires[] = [
                        'user_id' => $retraite->id,
                        'user_type' => 'retraite'
                    ];
                }
            }

            if (empty($destinataires)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun destinataire trouvé'
                ], 400);
            }

            // Utiliser la méthode existante avec les destinataires
            $requestData = $request->all();
            $requestData['destinataires'] = $destinataires;
            
            $newRequest = new Request($requestData);
            return $this->envoyerMessage($newRequest);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi global: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi global'
            ], 500);
        }
    }

    /**
     * Historique des messages envoyés par l'admin
     */
    public function historique(Request $request)
    {
        try {
            $query = MessageDashboard::with(['agent', 'retraite'])
                ->where('admin_id', auth('admin')->id());

            // Filtres
            if ($request->filled('type_message')) {
                $query->where('type_message', $request->type_message);
            }

            if ($request->filled('priorite')) {
                $query->where('priorite', $request->priorite);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('user_type')) {
                $query->where('user_type', $request->user_type);
            }

            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            // Tri
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $messages = $query->paginate($request->get('per_page', 15));

            // Ajouter des informations sur le destinataire
            $messages->getCollection()->transform(function ($message) {
                if ($message->user_type === 'agent' && $message->agent) {
                    $message->destinataire_nom = $message->agent->nom_complet;
                    $message->destinataire_matricule = $message->agent->matricule;
                } elseif ($message->user_type === 'retraite' && $message->retraite) {
                    $message->destinataire_nom = $message->retraite->nom_complet;
                    $message->destinataire_matricule = $message->retraite->matricule;
                } else {
                    $message->destinataire_nom = 'Utilisateur supprimé';
                    $message->destinataire_matricule = '-';
                }
                
                $message->expire_bientot = $message->expire_le && 
                    $message->expire_le->isBetween(now(), now()->addDays(3));
                
                return $message;
            });

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Messages d'un utilisateur spécifique
     */
    public function getMessagesUtilisateur($userId, $userType)
    {
        try {
            if (!in_array($userType, ['agent', 'retraite'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type d\'utilisateur invalide'
                ], 400);
            }

            // Vérifier que l'utilisateur existe
            if ($userType === 'agent') {
                $utilisateur = Agent::find($userId);
            } else {
                $utilisateur = Retraite::find($userId);
            }

            if (!$utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $messages = MessageDashboard::with('admin')
                ->where('user_id', $userId)
                ->where('user_type', $userType)
                ->orderBy('created_at', 'desc')
                ->get();

            // Statistiques
            $statistiques = [
                'total' => $messages->count(),
                'non_lus' => $messages->where('statut', 'non_lu')->count(),
                'lus' => $messages->where('statut', 'lu')->count(),
                'par_type' => $messages->groupBy('type_message')->map->count(),
                'par_priorite' => $messages->groupBy('priorite')->map->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'utilisateur' => [
                        'id' => $utilisateur->id,
                        'nom_complet' => $utilisateur->nom_complet,
                        'matricule' => $utilisateur->matricule,
                        'type' => $userType
                    ],
                    'messages' => $messages,
                    'statistiques' => $statistiques
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des messages utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages'
            ], 500);
        }
    }

    /**
     * Marquer un message comme lu/non lu
     */
    public function changerStatutMessage(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:lu,non_lu,archive'
        ]);

        try {
            $message = MessageDashboard::findOrFail($id);
            
            $ancienStatut = $message->statut;
            $message->update([
                'statut' => $request->statut,
                'date_lecture' => $request->statut === 'lu' ? now() : null
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'modification_statut_message',
                "Statut du message #{$id} changé de {$ancienStatut} à {$request->statut}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'data' => $message->fresh()
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
     * Supprimer un message
     */
    public function supprimerMessage($id)
    {
        try {
            $message = MessageDashboard::findOrFail($id);
            
            // Log avant suppression
            auth('admin')->user()->enregistrerActivite(
                'suppression_message',
                "Message #{$id} supprimé. Titre: {$message->titre}"
            );
            
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Statistiques des messages
     */
    public function statistiquesMessages()
    {
        try {
            $today = now();
            $startOfMonth = $today->copy()->startOfMonth();
            $startOfWeek = $today->copy()->startOfWeek();

            $statistiques = [
                'globales' => [
                    'total_messages' => MessageDashboard::count(),
                    'non_lus' => MessageDashboard::where('statut', 'non_lu')->count(),
                    'lus' => MessageDashboard::where('statut', 'lu')->count(),
                    'archives' => MessageDashboard::where('statut', 'archive')->count()
                ],
                'periode' => [
                    'envoyes_cette_semaine' => MessageDashboard::where('created_at', '>=', $startOfWeek)->count(),
                    'envoyes_ce_mois' => MessageDashboard::where('created_at', '>=', $startOfMonth)->count(),
                    'lus_cette_semaine' => MessageDashboard::where('date_lecture', '>=', $startOfWeek)->count(),
                    'lus_ce_mois' => MessageDashboard::where('date_lecture', '>=', $startOfMonth)->count()
                ],
                'par_type' => MessageDashboard::selectRaw('type_message, count(*) as total')
                    ->groupBy('type_message')
                    ->get()
                    ->pluck('total', 'type_message'),
                'par_priorite' => MessageDashboard::selectRaw('priorite, count(*) as total')
                    ->groupBy('priorite')
                    ->get()
                    ->pluck('total', 'priorite'),
                'par_destinataire' => [
                    'agents' => MessageDashboard::where('user_type', 'agent')->count(),
                    'retraites' => MessageDashboard::where('user_type', 'retraite')->count()
                ],
                'taux_lecture' => MessageDashboard::count() > 0 ? 
                    round(MessageDashboard::where('statut', 'lu')->count() * 100 / MessageDashboard::count(), 2) : 0
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

    /**
     * Messages expirés ou qui expirent bientôt
     */
    public function messagesExpiration()
    {
        try {
            $today = now();
            
            $expires = MessageDashboard::with(['agent', 'retraite'])
                ->where('expire_le', '<', $today)
                ->where('statut', '!=', 'archive')
                ->get();

            $expireBientot = MessageDashboard::with(['agent', 'retraite'])
                ->whereBetween('expire_le', [$today, $today->copy()->addDays(7)])
                ->where('statut', '!=', 'archive')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'expires' => $expires,
                    'expire_bientot' => $expireBientot,
                    'resume' => [
                        'total_expires' => $expires->count(),
                        'total_expire_bientot' => $expireBientot->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des messages d\'expiration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages d\'expiration'
            ], 500);
        }
    }

    /**
     * Archiver automatiquement les messages expirés
     */
    public function archiverMessagesExpires()
    {
        try {
            $messagesExpires = MessageDashboard::where('expire_le', '<', now())
                ->where('statut', '!=', 'archive')
                ->update([
                    'statut' => 'archive'
                ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'archivage_messages_expires',
                "Archivage automatique de {$messagesExpires} message(s) expiré(s)"
            );

            return response()->json([
                'success' => true,
                'message' => "{$messagesExpires} message(s) archivé(s) automatiquement",
                'data' => ['messages_archives' => $messagesExpires]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage'
            ], 500);
        }
    }
}