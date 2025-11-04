<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Récupérer toutes les conversations de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';
            
            $conversations = Conversation::pourUtilisateur($userType, $user->id)
                ->with(['dernierMessage', 'admin'])
                ->withCount('messagesNonLus')
                ->orderBy('derniere_activite', 'desc')
                ->get()
                ->map(function ($conversation) {
                    // ✅ CORRECTION UTF-8 pour dernier message
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
        'admin_nom' => $conversation->admin ? mb_convert_encoding($conversation->admin->nom_complet, 'UTF-8', 'UTF-8') : 'Non assigné',
        'dernier_message' => $conversation->dernierMessage ? [
            'message' => $dernierMessageText . '...',
            'created_at' => $conversation->dernierMessage->created_at->format('d/m/Y H:i'),
            'formatted_time' => $conversation->dernierMessage->formatted_time,
        ] : null,
        'unread_count' => $conversation->messages_non_lus_count,
        'derniere_activite' => $conversation->derniere_activite->format('d/m/Y H:i'),
        'created_at' => $conversation->created_at->format('d/m/Y H:i'),
    ];
});
            return response()->json([
                'success' => true,
                'conversations' => $conversations,
                'total_unread' => $conversations->sum('unread_count'),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération conversations', [
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
     * Créer une nouvelle conversation
     */
    public function store(Request $request)
    {
        try {
            Log::info('Création conversation - Données reçues', [
                'all_data' => $request->all(),
                'files' => $request->hasFile('attachments') ? 'Oui' : 'Non',
                'user_type' => get_class($request->user()),
            ]);

            $validator = Validator::make($request->all(), [
                'sujet' => 'required|string|max:255',
                'message' => 'required|string',
                'categorie' => 'nullable|string',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
                'template_code' => 'nullable|string',
                'attachments.*' => 'nullable|file|max:10240',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation échouée', [
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

            Log::info('Début transaction');
            DB::beginTransaction();

            $conversation = Conversation::create([
                'user_type' => $userType,
                'user_id' => $user->id,
                'admin_id' => $this->assignerAdmin(),
                'sujet' => $request->sujet,
                'statut' => 'ouvert',
                'priorite' => $request->priorite ?? 'normale',
                'categorie' => $request->categorie,
            ]);

            Log::info('Conversation créée', ['id' => $conversation->id]);

            $messageFiltre = Message::filtrerContenu($request->message);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => $userType,
                'sender_id' => $user->id,
                'message' => $messageFiltre,
                'is_template' => $request->template_code ? true : false,
                'template_type' => $request->template_code,
                'ip_address' => $request->ip(),
            ]);

            Log::info('Message créé', ['id' => $message->id]);

            if ($request->hasFile('attachments')) {
                Log::info('Traitement des fichiers');
                foreach ($request->file('attachments') as $index => $file) {
                    try {
                        Log::info('Fichier ' . $index, [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                        ]);
                        $message->ajouterPieceJointe($file);
                    } catch (\Exception $e) {
                        Log::error('Erreur ajout fichier ' . $index, [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
            Log::info('Transaction commit');

            return response()->json([
                'success' => true,
                'message' => 'Conversation créée avec succès',
                'conversation' => [
                    'id' => $conversation->id,
                    'numero_ticket' => $conversation->numero_ticket,
                    'sujet' => $conversation->sujet,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur création conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une conversation avec ses messages
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

            // ✅ RETIRE .sender car cette relation n'existe plus
            $conversation = Conversation::with(['admin', 'messages'])
                ->pourUtilisateur($userType, $user->id)
                ->findOrFail($id);

            // Marquer les messages comme lus
            $conversation->marquerMessagesCommeLus($userType, $user->id);

            $messages = $conversation->messages->map(function ($message) use ($userType, $user) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_name' => $message->sender_name, // ✅ Utilise l'accessor
                    'sender_type' => $message->sender_type,
                    'is_own' => $message->sender_type === $userType && $message->sender_id === $user->id,
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
                    'created_at' => $conversation->created_at->format('d/m/Y H:i'),
                ],
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Envoyer un message dans une conversation
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
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

            $conversation = Conversation::pourUtilisateur($userType, $user->id)
                ->findOrFail($id);

            $messageFiltre = Message::filtrerContenu($request->message);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => $userType,
                'sender_id' => $user->id,
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

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_name' => $message->sender_name,
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                    'formatted_time' => $message->formatted_time,
                    'attachments' => $message->getAttachmentsFormatted(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur envoi message', [
                'error' => $e->getMessage(),
                'conversation_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   /**
     * Récupérer les templates de messages
     */
    public function templates()
    {
        try {
            $templates = MessageTemplate::getTemplatesQuestions();

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
     * Récupérer le nombre de messages non lus
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

            $count = Message::whereHas('conversation', function ($query) use ($userType, $user) {
                    $query->pourUtilisateur($userType, $user->id);
                })
                ->where('is_read', false)
                ->where(function ($query) use ($userType, $user) {
                    $query->where('sender_type', '!=', $userType)
                          ->orWhere('sender_id', '!=', $user->id);
                })
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur unread count', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
            ], 500);
        }
    }

    /**
     * Assigner un admin à une conversation
     */
    private function assignerAdmin()
    {
        try {
            $admin = \App\Models\Admin::first();
            return $admin ? $admin->id : null;
        } catch (\Exception $e) {
            Log::warning('Pas de table admin', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
 * Modifier un message
 */
public function updateMessage(Request $request, $id)
{
    try {
        $user = $request->user();
        $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

        $message = Message::findOrFail($id);

        // Vérifier que c'est bien son message
        if ($message->sender_type !== $userType || $message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier ce message',
            ], 403);
        }

        // Vérifier que le message a moins de 15 minutes
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez plus modifier ce message (délai dépassé)',
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

        $messageFiltre = Message::filtrerContenu($request->message);
        $message->message = $messageFiltre;
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
        Log::error('Erreur modification message', [
            'error' => $e->getMessage(),
            'message_id' => $id,
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la modification',
        ], 500);
    }
}

/**
 * Supprimer un message
 */
public function deleteMessage(Request $request, $id)
{
    try {
        $user = $request->user();
        $userType = get_class($user) === 'App\Models\Agent' ? 'agent' : 'retraite';

        $message = Message::findOrFail($id);

        // Vérifier que c'est bien son message
        if ($message->sender_type !== $userType || $message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer ce message',
            ], 403);
        }

        // Vérifier que le message a moins de 15 minutes
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez plus supprimer ce message (délai dépassé)',
            ], 403);
        }

        // Soft delete ou hard delete selon préférence
        $message->delete(); // Soft delete si vous utilisez SoftDeletes
        // $message->forceDelete(); // Hard delete

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé avec succès',
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur suppression message', [
            'error' => $e->getMessage(),
            'message_id' => $id,
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression',
        ], 500);
    }
}
}