<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\LogActivite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Connexion administrateur
     */
    public function login(Request $request)
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ], [
                'email.required' => 'L\'email est obligatoire',
                'email.email' => 'Format d\'email invalide',
                'password.required' => 'Le mot de passe est obligatoire',
                'password.min' => 'Le mot de passe doit contenir au moins 6 caractères'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Chercher l'admin
            $admin = Admin::where('email', $request->email)->first();

            if (!$admin) {
                Log::warning('Tentative de connexion avec email inexistant: ' . $request->email, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            // Vérifier le statut de l'admin
            if ($admin->statut !== 'actif') {
                Log::warning('Tentative de connexion avec compte non actif: ' . $request->email, [
                    'admin_id' => $admin->id,
                    'statut' => $admin->statut,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte administrateur n\'est pas actif. Contactez le super administrateur.',
                    'statut_compte' => $admin->statut
                ], 403);
            }

            // Vérifier le mot de passe
            if (!Hash::check($request->password, $admin->password)) {
                Log::warning('Tentative de connexion avec mot de passe incorrect: ' . $request->email, [
                    'admin_id' => $admin->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            // Supprimer les anciens tokens
            $admin->tokens()->delete();

            // Créer un nouveau token
            $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

            // Mettre à jour la dernière connexion
            $admin->update([
                'derniere_connexion' => now()
            ]);

            // Enregistrer l'activité de connexion
            LogActivite::create([
                'admin_id' => $admin->id,
                'action' => 'connexion',
                'details' => 'Connexion administrateur réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            Log::info('Connexion admin réussie', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'role' => $admin->role,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'nom' => $admin->nom,
                        'prenom' => $admin->prenom,
                        'email' => $admin->email,
                        'role' => $admin->role,
                        'nom_complet' => $admin->nom_complet,
                        'initiales' => $admin->initiales,
                        'derniere_connexion' => $admin->derniere_connexion
                    ],
                    'token' => $token,
                    'permissions' => $this->getPermissions($admin->role)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion admin: ' . $e->getMessage(), [
                'email' => $request->email ?? 'non fourni',
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Déconnexion administrateur
     */
    public function logout(Request $request)
    {
        try {
            $admin = $request->user();

            if ($admin) {
                // Enregistrer l'activité de déconnexion
                LogActivite::create([
                    'admin_id' => $admin->id,
                    'action' => 'deconnexion',
                    'details' => 'Déconnexion administrateur',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                // Supprimer le token actuel
                $request->user()->currentAccessToken()->delete();

                Log::info('Déconnexion admin', [
                    'admin_id' => $admin->id,
                    'email' => $admin->email
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion admin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    /**
     * Informations de l'admin connecté
     */
    public function me(Request $request)
    {
        try {
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin non authentifié'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'nom' => $admin->nom,
                        'prenom' => $admin->prenom,
                        'email' => $admin->email,
                        'telephone' => $admin->telephone,
                        'role' => $admin->role,
                        'statut' => $admin->statut,
                        'nom_complet' => $admin->nom_complet,
                        'initiales' => $admin->initiales,
                        'derniere_connexion' => $admin->derniere_connexion,
                        'created_at' => $admin->created_at
                    ],
                    'permissions' => $this->getPermissions($admin->role),
                    'statistiques_personnelles' => [
                        'rdv_traites' => $admin->rendezVousTraites()->count(),
                        'reclamations_traitees' => $admin->reclamationsTraitees()->count(),
                        'documents_traites' => $admin->documentsTraites()->count(),
                        'messages_envoyes' => $admin->messagesEnvoyes()->count(),
                        'connexions_ce_mois' => LogActivite::where('admin_id', $admin->id)
                            ->where('action', 'connexion')
                            ->whereMonth('created_at', now()->month)
                            ->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des infos admin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations'
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function changerMotDePasse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mot_de_passe_actuel' => 'required|string',
                'nouveau_mot_de_passe' => 'required|string|min:8|confirmed',
                'nouveau_mot_de_passe_confirmation' => 'required|string'
            ], [
                'mot_de_passe_actuel.required' => 'Le mot de passe actuel est obligatoire',
                'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire',
                'nouveau_mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
                'nouveau_mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = $request->user();

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->mot_de_passe_actuel, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 401);
            }

            // Mettre à jour le mot de passe
            $admin->update([
                'password' => Hash::make($request->nouveau_mot_de_passe)
            ]);

            // Enregistrer l'activité
            LogActivite::create([
                'admin_id' => $admin->id,
                'action' => 'changement_mot_de_passe',
                'details' => 'Changement de mot de passe administrateur',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            Log::info('Changement de mot de passe admin', [
                'admin_id' => $admin->id,
                'email' => $admin->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de mot de passe admin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe'
            ], 500);
        }
    }

    /**
     * Vérifier le token
     */
    public function verifierToken(Request $request)
    {
        try {
            $admin = $request->user();

            if (!$admin || $admin->statut !== 'actif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou compte inactif'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token valide',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'email' => $admin->email,
                        'role' => $admin->role,
                        'nom_complet' => $admin->nom_complet
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 401);
        }
    }

    /**
     * Obtenir les permissions selon le rôle
     */
    private function getPermissions($role)
    {
        $permissions = [
            'admin1' => [
                'gerer_rdv',
                'gerer_reclamations',
                'gerer_documents',
                'envoyer_messages',
                'voir_statistiques',
                'exporter_donnees'
            ],
            'admin2' => [
                'gerer_rdv',
                'gerer_reclamations',
                'gerer_documents',
                'envoyer_messages',
                'voir_statistiques',
                'exporter_donnees'
            ],
            'super_admin' => [
                'gerer_rdv',
                'gerer_reclamations',
                'gerer_documents',
                'envoyer_messages',
                'voir_statistiques',
                'exporter_donnees',
                'gerer_admins',
                'voir_logs',
                'configuration_systeme'
            ]
        ];

        return $permissions[$role] ?? [];
    }
}