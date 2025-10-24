<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Récupérer toutes les notifications de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $userType = $request->get('user_type', 'actif');

            $notifications = Notification::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function($notif) {
                    return [
                        'id' => $notif->id,
                        'type' => $notif->type,
                        'titre' => $notif->titre,
                        'message' => $notif->message,
                        'lien' => $notif->lien,
                        'lu' => $notif->lu,
                        'date' => $notif->created_at->format('d/m/Y à H:i'),
                        'temps_ecoule' => $notif->temps_ecoule
                    ];
                });

            $non_lues = Notification::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('lu', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'non_lues' => $non_lues
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications'
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLue($id)
    {
        try {
            $user = auth()->user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $notification->update(['lu' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesLues(Request $request)
    {
        try {
            $user = auth()->user();
            $userType = $request->get('user_type', 'actif');

            Notification::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('lu', false)
                ->update(['lu' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications marquées comme lues'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur marquage notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function supprimer($id)
    {
        try {
            $user = auth()->user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}