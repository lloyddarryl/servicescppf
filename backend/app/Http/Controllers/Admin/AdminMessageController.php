<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Agent;
use App\Models\Retraite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminMessageController extends Controller
{
    /**
     * Récupérer toutes les conversations (avec filtres)
     */
    public function index(Request $request)
    {
        try {
            Log::info('🔍 AdminMessageController::index - Début', [
                'filters' => $request->all()
            ]);

            // ✅ Utiliser get() au lieu de paginate()
            $query = Conversation::with(['dernierMessage', 'admin'])
                ->withCount('messagesNonLus');

            // Filtres (seulement si valeur non vide)
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('priorite')) {
                $query->where('priorite', $request->priorite);
            }

            if ($request->filled('categorie')) {
                $query->where('categorie', $request->categorie);
            }

            if ($request->filled('admin_id')) {
                $query->where('admin_id', $request->admin_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_ticket', 'LIKE', "%{$search}%")
                      ->orWhere('sujet', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $query->orderBy('derniere_activite', 'desc');

            Log::info('SQL Query:', ['sql' => $query->toSql()]);

            // ✅ CORRECTION: Utiliser get() au lieu de paginate()
            $conversations = $query->get();

            Log::info('Conversations récupérées:', ['count' => $conversations->count()]);

// Mapper les conversations
$data = $conversations->map(function ($conversation) {
    try {
        // Récupérer l'utilisateur
        $userName = 'Utilisateur inconnu';
        $userType = $conversation->user_type;
        
        if ($userType === 'agent') {
            $user = Agent::find($conversation->user_id);
            // ✅ CORRECTION UTF-8: utiliser mb_convert_encoding
            $userName = $user ? mb_convert_encoding($user->prenoms . ' ' . $user->nom, 'UTF-8', 'UTF-8') : 'Agent inconnu';
        } elseif ($userType === 'retraite') {
            $user = Retraite::find($conversation->user_id);
            // ✅ CORRECTION UTF-8
            $userName = $user ? mb_convert_encoding($user->prenoms . ' ' . $user->nom, 'UTF-8', 'UTF-8') : 'Retraité inconnu';
        }

        // ✅ CORRECTION UTF-8 pour le dernier message
        $dernierMessageText = null;
        if ($conversation->dernierMessage) {
            $messageText = substr($conversation->dernierMessage->message, 0, 100);
            $dernierMessageText = mb_convert_encoding($messageText, 'UTF-8', 'UTF-8');
        }

        return [
            'id' => $conversation->id,
            'numero_ticket' => $conversation->numero_ticket,
            'sujet' => mb_convert_encoding($conversation->sujet, 'UTF-8', 'UTF-8'), // ✅ CORRECTION
            'statut' => $conversation->statut,
            'statut_badge' => $conversation->statut_badge,
            'priorite' => $conversation->priorite,
            'priorite_badge' => $conversation->priorite_badge,
            'categorie' => $conversation->categorie,
            'user_type' => $conversation->user_type,
            'user_name' => $userName,
            'admin_nom' => $conversation->admin ? mb_convert_encoding($conversation->admin->nom_complet, 'UTF-8', 'UTF-8') : 'Non assigné',
            'dernier_message' => $conversation->dernierMessage ? [
                'message' => $dernierMessageText,
                'created_at' => $conversation->dernierMessage->created_at->format('d/m/Y H:i'),
                'formatted_time' => $conversation->dernierMessage->formatted_time,
            ] : null,
            'unread_count' => $conversation->messages_non_lus_count,
            'derniere_activite' => $conversation->derniere_activite->format('d/m/Y H:i'),
            'created_at' => $conversation->created_at->format('d/m/Y H:i'),
        ];
    } catch (\Exception $e) {
        Log::error('Erreur mapping conversation', [
            'conversation_id' => $conversation->id ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        return null;
    }
})->filter()->values();; // Filtrer les nulls et réindexer

            Log::info('✅ Mapping terminé:', ['count' => $data->count()]);

            // Calculer les stats
            $stats = $this->getStats();

            return response()->json([
                'success' => true,
                'conversations' => $data,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans AdminMessageController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une conversation avec tous les messages
     */
    public function show(Request $request, $id)
    {
        try {
            $conversation = Conversation::with(['messages', 'admin'])
                ->findOrFail($id);

            // Marquer les messages comme lus pour l'admin
            $conversation->messages()
                ->where('sender_type', '!=', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            // Récupérer les infos utilisateur
            $userInfo = null;
            if ($conversation->user_type === 'agent') {
                $user = Agent::find($conversation->user_id);
                if ($user) {
                    $userInfo = [
                        'type' => 'Agent',
                        'nom_complet' => $user->prenoms . ' ' . $user->nom,
                        'email' => $user->email ?? null,
                        'telephone' => $user->telephone ?? null,
                        'matricule' => $user->matricule_solde ?? null,
                    ];
                }
            } elseif ($conversation->user_type === 'retraite') {
                $user = Retraite::find($conversation->user_id);
                if ($user) {
                    $userInfo = [
                        'type' => 'Retraité',
                        'nom_complet' => $user->prenoms . ' ' . $user->nom,
                        'email' => $user->email ?? null,
                        'telephone' => $user->telephone ?? null,
                        'numero_pension' => $user->numero_pension ?? null,
                    ];
                }
            }

            $messages = $conversation->messages->map(function ($message) {
                $senderName = $message->sender_name;
                $senderIdentifier = '';

                if ($message->sender_type === 'admin') {
            $admin = \App\Models\Admin::find($message->sender_id);
            // ✅ CORRECTION UTF-8
            $senderName = $admin ? mb_convert_encoding('Admin - ' . $admin->prenoms . ' ' . $admin->nom, 'UTF-8', 'UTF-8') : 'Administrateur';
        } else if ($message->sender_type === 'retraite') {
            $retraite = \App\Models\Retraite::find($message->sender_id);
            if ($retraite) {
                // ✅ CORRECTION UTF-8
                $senderName = mb_convert_encoding($retraite->prenoms . ' ' . $retraite->nom, 'UTF-8', 'UTF-8');
                $senderIdentifier = $retraite->numero_pension;
            }
        } else if ($message->sender_type === 'agent') {
            $agent = \App\Models\Agent::find($message->sender_id);
            if ($agent) {
                // ✅ CORRECTION UTF-8
                $senderName = mb_convert_encoding($agent->prenoms . ' ' . $agent->nom, 'UTF-8', 'UTF-8');
                $senderIdentifier = $agent->matricule_solde;
            }
        }
                
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_name' => $senderName,
                    'sender_type' => $message->sender_type,
                    'sender_id' => $message->sender_id,
                    'sender_identifier' => $senderIdentifier,
                    'is_edited' => $message->is_edited ?? false, // ✅ AJOUTÉ
                    'edited_at' => $message->edited_at ? $message->edited_at->format('d/m/Y H:i') : null, // ✅ AJOUTÉ
                    'is_admin' => $message->sender_type === 'admin',
                    'is_system' => $message->is_system_message,
                    'attachments' => $message->getAttachmentsFormatted(),
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                    'formatted_time' => $message->formatted_time,
                    'is_read' => $message->is_read,
                ];
            });

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'numero_ticket' => $conversation->numero_ticket,
                    'sujet' => $conversation->sujet,
                    'statut' => $conversation->statut,
                    'statut_badge' => $conversation->statut_badge,
                    'priorite' => $conversation->priorite,
                    'priorite_badge' => $conversation->priorite_badge,
                    'categorie' => $conversation->categorie,
                    'admin_nom' => $conversation->admin ? $conversation->admin->nom_complet : 'Non assigné',
                    'notes_internes' => $conversation->notes_internes,
                    'created_at' => $conversation->created_at->format('d/m/Y H:i'),
                    'user_info' => $userInfo,
                ],
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur affichage conversation admin', [
                'conversation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable',
            ], 404);
        }
    }

    /**
     * Envoyer un message (réponse)
     */
    public function sendMessage(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string',
                'template_code' => 'nullable|string',
                'attachments.*' => 'nullable|file|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $admin = $request->user('admin');
            $conversation = Conversation::findOrFail($id);

            $messageFiltre = Message::filtrerContenu($request->message);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'message' => $messageFiltre,
                'is_template' => $request->template_code ? true : false,
                'template_type' => $request->template_code,
                'ip_address' => $request->ip(),
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $message->ajouterPieceJointe($file);
                }
            }

            $conversation->touch('derniere_activite');

            $admin->enregistrerActivite(
                'message_envoye',
                "Message envoyé dans {$conversation->numero_ticket}",
                $message
            );

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_name' => 'Admin - ' . $admin->prenoms . ' ' . $admin->nom,
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                    'formatted_time' => $message->formatted_time,
                    'attachments' => $message->getAttachmentsFormatted(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur envoi message admin', [
                'conversation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
            ], 500);
        }
    }

     /**
     * Statistiques des conversations
     */
    private function getStats()
    {
        return [
            'total' => Conversation::count(),
            'ouverts' => Conversation::ouverts()->count(),
            'en_cours' => Conversation::enCours()->count(),
            'resolus' => Conversation::resolus()->count(),
            'fermes' => Conversation::fermes()->count(),
            'urgents' => Conversation::urgents()->count(),
            'messages_non_lus' => Message::nonLus()->deUtilisateur()->count(),
        ];
    }

    /**
     * Créer des conversations groupées
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'destinataires' => 'required|array|min:1',
                'destinataires.*' => 'required|string',
                'sujet' => 'required|string|max:255',
                'message' => 'required|string',
                'categorie' => 'nullable|string',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $admin = $request->user('admin');
            DB::beginTransaction();

            $conversationsCreees = [];

            foreach ($request->destinataires as $destinataire) {
                [$userType, $userId] = explode(':', $destinataire);

                $conversation = Conversation::create([
                    'user_type' => $userType,
                    'user_id' => (int)$userId,
                    'admin_id' => $admin->id,
                    'sujet' => $request->sujet,
                    'statut' => 'ouvert',
                    'priorite' => $request->priorite ?? 'normale',
                    'categorie' => $request->categorie,
                ]);

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin',
                    'sender_id' => $admin->id,
                    'message' => $request->message,
                    'ip_address' => $request->ip(),
                ]);

                $conversationsCreees[] = $conversation->numero_ticket;
            }

            $admin->enregistrerActivite(
                'message_groupe_envoye',
                "Message groupé envoyé à " . count($request->destinataires) . " utilisateurs"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Messages envoyés avec succès',
                'conversations_creees' => $conversationsCreees,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création message groupé', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des messages',
            ], 500);
        }
    }

    /**
     * Mettre à jour une conversation
     */
    public function update(Request $request, $id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            $admin = $request->user('admin');

            $validator = Validator::make($request->all(), [
                'statut' => 'nullable|in:ouvert,en_cours,resolu,ferme',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
                'admin_id' => 'nullable|exists:admins,id',
                'notes_internes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $dataToUpdate = $request->only(['statut', 'priorite', 'admin_id', 'notes_internes']);

            if (isset($dataToUpdate['statut']) && $dataToUpdate['statut'] === 'resolu') {
                $dataToUpdate['resolu_le'] = now();
                $dataToUpdate['resolu_par'] = $admin->id;
            }

            $conversation->update($dataToUpdate);

            $admin->enregistrerActivite(
                'conversation_mise_a_jour',
                "Conversation {$conversation->numero_ticket} mise à jour",
                $conversation
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversation mise à jour avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour conversation', [
                'conversation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
            ], 500);
        }
    }

    /**
     * Récupérer les templates de réponses
     */
    public function templates()
    {
        try {
            $templates = MessageTemplate::getTemplatesReponses();

            return response()->json([
                'success' => true,
                'templates' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des templates',
            ], 500);
        }
    }

 

    /**
     * Rechercher des utilisateurs pour message groupé
     */
    public function searchUsers(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $type = $request->get('type', 'all');

            $results = [];

            if ($type === 'all' || $type === 'agent') {
                $agents = Agent::actifs()
                    ->where(function($q) use ($search) {
                        $q->where('nom', 'LIKE', "%{$search}%")
                          ->orWhere('prenoms', 'LIKE', "%{$search}%")
                          ->orWhere('matricule_solde', 'LIKE', "%{$search}%");
                    })
                    ->limit(20)
                    ->get()
                    ->map(function($agent) {
                        return [
                            'id' => "agent:{$agent->id}",
                            'nom' => mb_convert_encoding($agent->prenoms . ' ' . $agent->nom, 'UTF-8', 'UTF-8'),
                            'type' => 'Agent',
                            'matricule' => $agent->matricule_solde,
                        ];
                    });

                $results = array_merge($results, $agents->toArray());
            }

            if ($type === 'all' || $type === 'retraite') {
                $retraites = Retraite::active()
                    ->where(function($q) use ($search) {
                        $q->where('nom', 'LIKE', "%{$search}%")
                          ->orWhere('prenoms', 'LIKE', "%{$search}%")
                          ->orWhere('numero_pension', 'LIKE', "%{$search}%");
                    })
                    ->limit(20)
                    ->get()
                    ->map(function($retraite) {
                        return [
                            'id' => "retraite:{$retraite->id}",
                            'nom' => mb_convert_encoding($retraite->prenoms . ' ' . $retraite->nom, 'UTF-8', 'UTF-8'),
                            'type' => 'Retraité',
                            'numero_pension' => $retraite->numero_pension,
                        ];
                    });

                $results = array_merge($results, $retraites->toArray());
            }

            return response()->json([
                'success' => true,
                'users' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
            ], 500);
        }
    }

    /**
 * Modifier un message (admin)
 */
public function updateMessage(Request $request, $id)
{
    try {
        $admin = $request->user('admin');
        $message = Message::findOrFail($id);

        // Vérifier que c'est un message d'admin
        if ($message->sender_type !== 'admin' || $message->sender_id !== $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier ce message',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $message->message = $request->message;
        $message->is_edited = true;
        $message->edited_at = now();
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message modifié avec succès',
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'is_edited' => true,
                'edited_at' => $message->edited_at->format('d/m/Y H:i'),
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur modification message admin', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Erreur'], 500);
    }
}

/**
 * Supprimer un message (admin)
 */
public function deleteMessage(Request $request, $id)
{
    try {
        $admin = $request->user('admin');
        $message = Message::findOrFail($id);

        // Admin peut supprimer ses propres messages uniquement
        if ($message->sender_type !== 'admin' || $message->sender_id !== $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer ce message',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé avec succès',
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur suppression message admin', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Erreur'], 500);
    }
}
}