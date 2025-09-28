<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;
use App\Models\Retraite;
use App\Services\SmsServices;

class ProfileController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            
            // Déterminer le type d'utilisateur
            $userType = $user instanceof Agent ? 'actif' : 'retraite';
            
            $profileData = [
                'id' => $user->id,
                'type' => $userType,
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
                'first_login' => $user->first_login,
                'password_changed' => $user->password_changed,
                'pending_phone_number' => $user->pending_phone_number ?? null,
            ];

            // Ajouter des données spécifiques selon le type
            if ($userType === 'actif') {
                $profileData['matricule_solde'] = $user->matricule_solde;
                $profileData['poste'] = $user->poste;
                $profileData['direction'] = $user->direction;
                $profileData['grade'] = $user->grade;
                $profileData['date_prise_service'] = $user->date_prise_service;
                $profileData['sexe'] = $user->sexe;
            } else {
                $profileData['numero_pension'] = $user->numero_pension;
                $profileData['date_naissance'] = $user->date_naissance;
                $profileData['date_retraite'] = $user->date_retraite;
                $profileData['ancien_poste'] = $user->ancien_poste;
                $profileData['ancienne_direction'] = $user->ancienne_direction;
                $profileData['montant_pension'] = $user->montant_pension;
                $profileData['sexe'] = $user->sexe;
            }

            return response()->json([
                'success' => true,
                'profile' => $profileData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProfileController::show', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil'
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil - VERSION CORRIGÉE
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'actif' : 'retraite';

            $rules = [
                'email' => 'sometimes|email|unique:agents,email,' . $user->id,
                'telephone' => 'sometimes|string|regex:/^\+241[0-9]{8,9}$/',
            ];

            if ($userType === 'retraite') {
                $rules['email'] = 'sometimes|email|unique:retraites,email,' . $user->id;
            }

            $validator = Validator::make($request->all(), $rules, [
                'telephone.regex' => 'Le numéro de téléphone doit être au format +241XXXXXXXX'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            $needsPhoneVerification = false;

            // Mise à jour email
            if ($request->has('email') && $request->email !== $user->email) {
                $updateData['email'] = $request->email;
                $updateData['email_verified_at'] = null;
            }

            // Gestion du changement de téléphone
            if ($request->has('telephone')) {
                $newPhone = $request->telephone;
                
                if ($newPhone !== $user->telephone) {
                    // Stocker le nouveau numéro temporairement
                    $user->pending_phone_number = $newPhone;
                    $user->save();
                    
                    $needsPhoneVerification = true;
                    
                    Log::info('Nouveau téléphone en attente de vérification', [
                        'user_id' => $user->id,
                        'old_phone' => $user->telephone,
                        'pending_phone' => $newPhone
                    ]);
                }
            }

            // Mettre à jour les autres champs (sauf téléphone)
            if (!empty($updateData)) {
                $user->update($updateData);
            }

            $response = [
                'success' => true,
                'message' => 'Informations mises à jour avec succès'
            ];

            // Si changement de téléphone, déclencher la vérification
            if ($needsPhoneVerification) {
                $verificationResult = $this->sendPhoneVerificationForUpdate($user);
                
                if ($verificationResult['success']) {
                    $response['message'] = 'Code de vérification envoyé pour le nouveau numéro';
                    $response['phone_verification_required'] = true;
                    $response['pending_phone'] = $user->pending_phone_number;
                } else {
                    // Nettoyer le numéro en attente si erreur SMS
                    $user->pending_phone_number = null;
                    $user->save();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de l\'envoi du SMS : ' . $verificationResult['message']
                    ], 500);
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Erreur ProfileController::update', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du profil'
            ], 500);
        }
    }

    /**
     * Envoyer SMS pour mise à jour de profil
     */
    private function sendPhoneVerificationForUpdate($user)
    {
        try {
            if (!$user->pending_phone_number) {
                return ['success' => false, 'message' => 'Aucun numéro en attente'];
            }

            // Générer un code aléatoire à 6 chiffres
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Sauvegarder le code
            $user->verification_code = $verificationCode;
            $user->verification_code_expires_at = now()->addMinutes(15);
            $user->save();

            // Envoyer le SMS au NOUVEAU numéro
            $smsService = new SmsServices();
            $result = $smsService->sendVerificationCode($user->pending_phone_number, $verificationCode);

            Log::info('SMS envoyé pour changement téléphone', [
                'user_id' => $user->id,
                'new_phone' => $user->pending_phone_number,
                'sms_success' => $result['success']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS changement téléphone', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur technique lors de l\'envoi du SMS'
            ];
        }
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ], [
                'new_password.regex' => 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 400);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($request->new_password),
                'password_changed' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans ProfileController::changePassword', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du changement de mot de passe'
            ], 500);
        }
    }

    /**
     * Renvoyer le code de vérification via SMS
     */
    public function resendVerification(Request $request)
    {
        try {
            $user = $request->user();

            Log::debug('Début resendVerification profil', [
                'user_id' => $user->id,
                'phone' => $user->telephone,
                'pending_phone' => $user->pending_phone_number,
            ]);

            // Déterminer quel numéro utiliser
            $phoneToUse = $user->pending_phone_number ?? $user->telephone;

            if (!$phoneToUse) {
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

            // Envoyer le SMS
            $smsService = new SmsServices();
            $result = $smsService->sendVerificationCode($phoneToUse, $verificationCode);

            if (!$result['success']) {
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
            Log::error('Erreur dans ProfileController::resendVerification', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du SMS'
            ], 500);
        }
    }

    /**
     * Vérifier le code SMS - VERSION CORRIGÉE
     */
    public function verifyPhone(Request $request)
    {
        try {
            Log::debug('Début verifyPhone profil', [
                'request_data' => $request->all(),
                'user_id' => $request->user()->id
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

            $user = $request->user();
            $code = $request->verification_code;

            if (!$user->verification_code || 
                $user->verification_code !== $code || 
                now()->isAfter($user->verification_code_expires_at)) {
                
                Log::error('Échec vérification code profil', [
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

            // Appliquer le changement de téléphone si en attente
            if ($user->pending_phone_number) {
                $oldPhone = $user->telephone;
                $newPhone = $user->pending_phone_number;
                
                $user->telephone = $newPhone;
                $user->pending_phone_number = null;
                
                Log::info('Téléphone mis à jour après vérification', [
                    'user_id' => $user->id,
                    'old_phone' => $oldPhone,
                    'new_phone' => $newPhone
                ]);
            }

            $user->phone_verified_at = now();
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->save();

            Log::info('Téléphone vérifié pour utilisateur profil', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Numéro de téléphone vérifié avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans ProfileController::verifyPhone', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification'
            ], 500);
        }
    }
}