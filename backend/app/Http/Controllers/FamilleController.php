<?php
// File: app/Http/Controllers/FamilleController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;
use App\Models\Conjoint;
use App\Models\Enfant;
use App\Models\PrestationFamiliale;
use Carbon\Carbon;

class FamilleController extends Controller
{
    /**
     * Vérifier que l'utilisateur est un agent actif
     */
    private function checkUserIsAgent($user)
    {
        if (!($user instanceof Agent)) {
            return response()->json([
                'success' => false, 
                'message' => 'Accès non autorisé - Ce service est réservé aux agents actifs'
            ], 403);
        }
        return null;
    }

    /**
     * Obtenir la grappe familiale complète de l'agent
     */
    public function getGrappeFamiliale(Request $request)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            // Récupérer le conjoint actif
            $conjoint = Conjoint::where('agent_id', $user->id)
                ->actif()
                ->first();

            // Récupérer les enfants actifs
            $enfants = Enfant::where('agent_id', $user->id)
                ->actif()
                ->orderBy('date_naissance', 'desc')
                ->with('prestations')
                ->get();

            // Statistiques famille
            $stats = [
                'nombre_enfants' => $enfants->count(),
                'enfants_mineurs' => $enfants->where('age', '<', 18)->count(),
                'enfants_avec_prestations' => $enfants->where('prestation_familiale', true)->count(),
                'conjoint_presente' => $conjoint ? true : false,
                'conjoint_travaille' => $conjoint && $conjoint->travaille
            ];

            // Formater les données du conjoint
            $conjointData = null;
            if ($conjoint) {
                $conjointData = [
                    'id' => $conjoint->id,
                    'nom_complet' => $conjoint->nom_complet,
                    'nom' => $conjoint->nom,
                    'prenoms' => $conjoint->prenoms,
                    'sexe' => $conjoint->sexe,
                    'age' => $conjoint->age,
                    'date_naissance' => $conjoint->date_naissance->format('Y-m-d'),
                    'date_mariage' => $conjoint->date_mariage ? $conjoint->date_mariage->format('Y-m-d') : null,
                    'travaille' => $conjoint->travaille,
                    'identifiant' => $conjoint->identifiant,
                    'profession' => $conjoint->profession,
                    'statut' => $conjoint->statut
                ];
            }

            // Formater les données des enfants
            $enfantsData = $enfants->map(function ($enfant) {
                return [
                    'id' => $enfant->id,
                    'enfant_id' => $enfant->enfant_id,
                    'nom_complet' => $enfant->nom_complet,
                    'nom' => $enfant->nom,
                    'prenoms' => $enfant->prenoms,
                    'sexe' => $enfant->sexe,
                    'age' => $enfant->age,
                    'date_naissance' => $enfant->date_naissance->format('Y-m-d'),
                    'prestation_familiale' => $enfant->prestation_familiale,
                    'scolarise' => $enfant->scolarise,
                    'niveau_scolaire' => $enfant->niveau_scolaire,
                    'est_mineur' => $enfant->est_mineur,
                    'en_age_scolarite' => $enfant->en_age_scolarite,
                    'prestations_actives' => $enfant->prestations()->actif()->count()
                ];
            });

            return response()->json([
                'success' => true,
                'grappe_familiale' => [
                    'agent' => [
                        'id' => $user->id,
                        'nom_complet' => $user->prenoms . ' ' . $user->nom,
                        'matricule' => $user->matricule_solde,
                        'sexe' => $user->sexe ?? 'M',
                        'situation_matrimoniale' => $user->situation_matrimoniale
                    ],
                    'conjoint' => $conjointData,
                    'enfants' => $enfantsData,
                    'statistiques' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération grappe familiale:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données familiales'
            ], 500);
        }
    }

    /**
     * Ajouter ou modifier le conjoint
     */
    public function saveconjoint(Request $request)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'date_naissance' => 'required|date',
                'date_mariage' => 'nullable|date',
                'matricule_conjoint' => 'nullable|string|max:20',
                'nag_conjoint' => 'nullable|string|max:20',
                'profession' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['agent_id'] = $user->id;
            $data['travaille'] = !empty($data['matricule_conjoint']);

            // Vérifier s'il y a déjà un conjoint actif
            $conjoint = Conjoint::where('agent_id', $user->id)->actif()->first();

            if ($conjoint) {
                $conjoint->update($data);
            } else {
                $conjoint = Conjoint::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conjoint enregistré avec succès',
                'conjoint' => $conjoint
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde conjoint:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du conjoint'
            ], 500);
        }
    }

    /**
     * Ajouter un enfant
     */
    public function addEnfant(Request $request)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            $validator = Validator::make($request->all(), [
                'enfant_id' => 'required|string|max:20',
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'date_naissance' => 'required|date',
                'prestation_familiale' => 'boolean',
                'scolarise' => 'boolean',
                'niveau_scolaire' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['agent_id'] = $user->id;
            $data['matricule_parent'] = Enfant::generateMatriculeParent($user->matricule_solde);

            // Vérifier si l'enfant n'existe pas déjà
            $existant = Enfant::where('enfant_id', $data['enfant_id'])
                ->where('agent_id', $user->id)
                ->first();

            if ($existant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enfant est déjà déclaré'
                ], 409);
            }

            $enfant = Enfant::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Enfant ajouté avec succès',
                'enfant' => $enfant
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur ajout enfant:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'enfant'
            ], 500);
        }
    }

    /**
     * Modifier un enfant
     */
    public function updateEnfant(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            $enfant = Enfant::where('id', $id)
                ->where('agent_id', $user->id)
                ->first();

            if (!$enfant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enfant non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'date_naissance' => 'required|date',
                'prestation_familiale' => 'boolean',
                'scolarise' => 'boolean',
                'niveau_scolaire' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $enfant->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Enfant modifié avec succès',
                'enfant' => $enfant
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur modification enfant:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'enfant'
            ], 500);
        }
    }

    /**
     * Supprimer un enfant (désactivation)
     */
    public function deleteEnfant(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            $enfant = Enfant::where('id', $id)
                ->where('agent_id', $user->id)
                ->first();

            if (!$enfant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enfant non trouvé'
                ], 404);
            }

            // Désactiver au lieu de supprimer
            $enfant->update(['actif' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Enfant supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression enfant:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'enfant'
            ], 500);
        }
    }
}