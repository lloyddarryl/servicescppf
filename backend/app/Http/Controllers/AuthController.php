<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Agent;
use App\Models\Retraite;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken; 
use Laravel\Sanctum\HasApiTokens;
use App\Services\SmsServices;

class AuthController extends Controller
{
    /**
     * Première connexion pour les ACTIFS
     * Matricule solde (7 ou 13 caractères) + mot de passe temporaire
     */
    public function firstLoginActifs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule_solde' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (strlen($value) === 7) {
                        if (!preg_match('/^[0-9]{6}[A-Z]$/', $value)) {
                            $fail('Format 7 caractères invalide : 6 chiffres suivis d\'une lettre');
                        }
                    } elseif (strlen($value) === 13) {
                        if (!preg_match('/^[0-9]{12}[A-Z]$/', $value)) {
                            $fail('Format 13 caractères invalide : 12 chiffres suivis d\'une lettre');
                        }
                    } else {
                        $fail('Le matricule doit contenir 7 ou 13 caractères');
                    }
                }
            ],
            'password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $matricule = $request->matricule_solde;
                    if (strlen($matricule) === 7 && strlen($value) !== 6) {
                        $fail('Le mot de passe doit contenir 6 chiffres pour un matricule de 7 caractères');
                    } elseif (strlen($matricule) === 13 && strlen($value) !== 12) {
                        $fail('Le mot de passe doit contenir 12 chiffres pour un matricule de 13 caractères');
                    } elseif (!preg_match('/^[0-9]+$/', $value)) {
                        $fail('Le mot de passe doit contenir uniquement des chiffres');
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $matricule = $request->matricule_solde;
        $password = $request->password;

        // Vérifier que les chiffres du matricule correspondent au mot de passe
        $matriculeLength = strlen($matricule);
        $expectedPasswordLength = $matriculeLength === 7 ? 6 : 12;
        $matriculeNumbers = substr($matricule, 0, $expectedPasswordLength);
        
        if ($matriculeNumbers !== $password) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe temporaire incorrect'
            ], 401);
        }

        // Rechercher l'agent
        $agent = Agent::where('matricule_solde', $matricule)
                     ->where('is_active', true)
                     ->first();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Matricule non trouvé'
            ], 404);
        }

        // Vérifier si c'est la première connexion
        if (!$agent->first_login) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez utiliser la connexion standard',
                'redirect' => 'standard_login'
            ], 403);
        }

        // Générer le token pour la session de configuration
        $token = $agent->createToken('setup-session')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Première connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $agent->id,
                'matricule' => $agent->matricule_solde,
                'nom' => $agent->nom,
                'prenoms' => $agent->prenoms,
                'poste' => $agent->poste,
                'type' => 'actif',
                'first_login' => true
            ],
            'next_step' => 'setup_profile'
        ]);
    }

    /**
     * Première connexion pour les RETRAITÉS
     * Numéro de pension (chiffres uniquement) + date de naissance
     */
    public function firstLoginRetraites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_pension' => 'required|string|regex:/^[0-9]+$/',
            'date_naissance' => 'required|date|before:today'
        ], [
            'numero_pension.regex' => 'Le numéro de pension doit contenir uniquement des chiffres',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $numeroPension = $request->numero_pension;
        $dateNaissance = $request->date_naissance;

        // Rechercher le retraité
        $retraite = Retraite::where('numero_pension', $numeroPension)
                           ->where('date_naissance', $dateNaissance)
                           ->where('is_active', true)
                           ->first();

        if (!$retraite) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de pension ou date de naissance incorrects'
            ], 404);
        }

        // Vérifier si c'est la première connexion
        if (!$retraite->first_login) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez utiliser la connexion standard',
                'redirect' => 'standard_login'
            ], 403);
        }

        // Générer le token pour la session de configuration
        $token = $retraite->createToken('setup-session')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Première connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $retraite->id,
                'numero_pension' => $retraite->numero_pension,
                'nom' => $retraite->nom,
                'prenoms' => $retraite->prenoms,
                'ancien_poste' => $retraite->ancien_poste,
                'type' => 'retraite',
                'first_login' => true
            ],
            'next_step' => 'setup_profile'
        ]);
    }

    /**
     * Configuration du profil après première connexion
     * Email, téléphone, nouveau mot de passe
     */
    public function setupProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:agents,email|unique:retraites,email',
            'telephone' => 'required|string|regex:/^[0-9]{8,9}$/',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'user_type' => 'required|in:actif,retraite',
            'user_id' => 'required|integer'
        ], [
            'telephone.regex' => 'Le numéro doit contenir 8 ou 9 chiffres',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userType = $request->user_type;
        $userId = $request->user_id;
        $telephone = '+241' . $request->telephone; // Indicatif Gabon par défaut

        // Mettre à jour selon le type d'utilisateur
        if ($userType === 'actif') {
            $user = Agent::find($userId);
        } else {
            $user = Retraite::find($userId);
        }

        if (!$user || !$user->first_login) {
            return response()->json([
                'success' => false,
                'message' => 'Session invalide'
            ], 403);
        }

        // Mettre à jour les informations
        $user->email = $request->email;
        $user->telephone = $telephone;
        $user->password = Hash::make($request->password);
        $user->password_changed = true;
        $user->save();

        // Générer et envoyer le code de vérification immédiatement après la configuration du profil
    try {
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = now()->addMinutes(15);
        $user->save();

         Log::info('Code SMS généré pour setup', [
            'user_type' => $userType,
            'user_id' => $user->id,
            'phone' => $user->telephone,
            'code' => $verificationCode
        ]);

        $smsService = new \App\Services\SmsServices(); // Assurez-vous que le namespace est correct
        $result = $smsService->sendVerificationCode($user->telephone, $verificationCode);
        if (!$result['success']) {
            Log::error('Échec envoi SMS setup', [
                'user_type' => $userType,
                'user_id' => $user->id,
                'sms_error' => $result['message']
            ]);
        } else {
            Log::info('SMS setup envoyé avec succès', [
                'user_type' => $userType,
                'user_id' => $user->id
            ]);
        }   
    } catch (\Exception $e) {
        Log::error('Erreur génération/envoi SMS setup', [
            'user_type' => $userType,
            'user_id' => $user->id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Profil configuré avec succès',
        'next_step' => 'phone_verification',
        'phone' => $telephone
    ]);
}


    /**
     * Connexion standard (après configuration initiale)
     */
    public function standardLogin(Request $request)
    {
        // Normaliser le user_type AVANT la validation
        $userType = $request->user_type;
        if ($userType === 'actifs') {
            $userType = 'actif';
        } elseif ($userType === 'retraites') {
            $userType = 'retraite';
        }

        // Validation avec le type normalisé
        $validator = Validator::make([
            'identifier' => $request->identifier,
            'password' => $request->password,
            'user_type' => $userType
        ], [
            'identifier' => 'required|string',
            'password' => 'required|string',
            'user_type' => 'required|in:actif,retraite'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->identifier;
        $password = $request->password;

        // Rechercher l'utilisateur selon le type
        if ($userType === 'actif') {
            $user = Agent::where('matricule_solde', $identifier)
                         ->where('is_active', true)
                         ->where('password_changed', true)
                         ->first();
        } else {
            $user = Retraite::where('numero_pension', $identifier)
                           ->where('is_active', true)
                           ->where('password_changed', true)
                           ->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé ou profil non configuré'
            ], 404);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ], 401);
        }


        // ✅ NOUVEAU : Révoquer tous les tokens existants pour éviter les sessions multiples
        $user->tokens()->delete();

        // Générer le token de session
        $token = $user->createToken('auth-session')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'type' => $userType,
                'identifier' => $identifier,
                'poste' => $userType === 'actif' ? $user->poste : $user->ancien_poste
            ],
            'redirect' => 'dashboard'
        ]);
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function getCurrentUser(Request $request)
    {
        $user = $request->user();
        $userType = $user instanceof Agent ? 'actif' : 'retraite';
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'type' => $userType,
                'poste' => $userType === 'actif' ? $user->poste : $user->ancien_poste
            ]
        ]);
    }

    /**
     * Vérifier le token
     */
    public function verifyToken(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Token valide'
        ]);
    }

    /**
     * Nettoyer les données de setup incomplètes
     * (pour les utilisateurs qui n'ont pas terminé leur configuration)
     * Cette méthode est temporaire pour nettoyer les données en cas de problème
     */
    public function cleanupSetup(Request $request)
    {
        try {
            $user = $request->user();
        
            if ($user && $user->first_login) {
                // Nettoyer les données partielles
                $user->email = null;
                $user->telephone = null;
                $user->verification_code = null;
                $user->verification_code_expires_at = null;
                $user->save();
            
                // Révoquer le token de setup
                $request->user()->currentAccessToken()->delete();
        }

            return response()->json([
                'success' => true,
                'message' => 'Données nettoyées'
            ]);
        
        } catch (\Exception $e) {
            return response()->json([
            'success' => false,
            'message' => 'Erreur lors du nettoyage'
        ], 500);
    }
}


    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }
/**
     * Renvoyer le code de vérification via SMS (pendant le setup)
     */
    public function resendVerificationSetup(Request $request)
{
    try {
        // Récupérer l'utilisateur depuis le token
        $token = $request->bearerToken();
        if (!$token) {
            Log::error('Token manquant dans resendVerificationSetup');
            return response()->json([
                'success' => false,
                'message' => 'Token manquant'
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            Log::error('Token invalide dans resendVerificationSetup', ['token' => substr($token, 0, 10) . '...']);
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 401);
        }

        $user = $accessToken->tokenable;

        // ✅ CORRECTION : Supporter Agent ET Retraite
        $userType = $user instanceof Agent ? 'actif' : 'retraite';

        Log::debug('Début resendVerificationSetup', [
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'phone' => $user->telephone,
            'first_login' => $user->first_login
        ]);

        if (!$user->telephone) {
            Log::error('Téléphone manquant', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Aucun numéro de téléphone configuré'
            ], 400);
        }

        // Générer un code aléatoire à 6 chiffres
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Sauvegarder le code
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = now()->addMinutes(15);
        $user->save();

        Log::debug('Code de vérification généré', [
            'user_id' => $user->id,
            'code' => $verificationCode,
            'expires_at' => $user->verification_code_expires_at
        ]);

        // Envoyer le SMS via l'API
        $smsService = new SmsServices();
        $result = $smsService->sendVerificationCode($user->telephone, $verificationCode);

        if (!$result['success']) {
            Log::error('Échec envoi SMS', [
                'user_id' => $user->id,
                'sms_error' => $result['message']
            ]);
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code de vérification envoyé par SMS'
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur resendVerificationSetup', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'envoi du SMS'
        ], 500);
    }
}


    /**
     * Vérifier le code SMS (pendant le setup)
     */
    public function verifyPhoneSetup(Request $request)
    {
        try {
            Log::debug('Début verifyPhoneSetup', [
                'request_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'verification_code' => 'required|string|size:6|regex:/^[0-9]{6}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Récupérer l'utilisateur depuis le token
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token manquant'
                ], 401);
            }

            $accessToken = PersonalAccessToken::findToken($token);
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide'
                ], 401);
            }

            $user = $accessToken->tokenable;
            $code = $request->verification_code;

            $userType = $user instanceof Agent ? 'actif' : 'retraite';


            Log::debug('Vérification du code', [
                'user_id' => $user->id,
                'provided_code' => $code,
                'stored_code' => $user->verification_code,
                'expires_at' => $user->verification_code_expires_at,
                'now' => now()
            ]);

            if (!$user->verification_code || 
                $user->verification_code !== $code || 
                now()->isAfter($user->verification_code_expires_at)) {
                
                Log::error('Échec vérification code setup', [
                    'user_id' => $user->id,
                    'stored_code' => $user->verification_code,
                    'provided_code' => $code,
                    'code_expired' => now()->isAfter($user->verification_code_expires_at)
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Code de vérification invalide ou expiré'
                ], 400);
            }

            // Marquer comme vérifié et terminer le setup
            $user->phone_verified_at = now();
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->first_login = false; // Terminer le processus de setup
            $user->save();

            Log::info('Setup terminé pour utilisateur', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Numéro de téléphone vérifié avec succès. Setup terminé.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du téléphone setup', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification'
            ], 500);
        }
    }
}
