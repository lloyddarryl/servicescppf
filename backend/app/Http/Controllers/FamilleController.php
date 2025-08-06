<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\Conjoint;
use App\Models\Enfant;
use App\Models\PrestationFamiliale;
use Carbon\Carbon;

class FamilleController extends Controller
{
    /**
     * Vérifier que l'utilisateur est un agent actif OU un retraité
     */
    private function checkUserAccess($user)
    {
        // Permettre l'accès aux agents ET aux retraités
        if (!($user instanceof Agent) && !($user instanceof Retraite)) {
            return response()->json([
                'success' => false, 
                'message' => 'Accès non autorisé - Ce service est réservé aux utilisateurs authentifiés'
            ], 403);
        }
        return null;
    }

    /**
     * Obtenir la grappe familiale pour agent actif OU retraité
     */
    public function getGrappeFamiliale(Request $request)
    {
        try {
            $user = $request->user();

            // ✅ DEBUG AMÉLIORÉ
        Log::info('🔍 DEBUG Grappe Familiale - Début', [
            'user_id' => $user->id ?? 'NULL',
            'user_class' => get_class($user),
            'user_table' => method_exists($user, 'getTable') ? $user->getTable() : 'UNKNOWN',
            'is_agent' => $user instanceof \App\Models\Agent,
            'is_retraite' => $user instanceof \App\Models\Retraite,
            'user_attributes_keys' => array_keys($user->getAttributes()),
        ]);
            
            $checkResult = $this->checkUserAccess($user);
            if ($checkResult) return $checkResult;

            // Déterminer le type d'utilisateur et l'ID approprié
            $isAgent = $user instanceof Agent;
            $userType = $isAgent ? 'actif' : 'retraite';

             Log::info('✅ Type utilisateur déterminé', [
            'userType' => $userType,
            'isAgent' => $isAgent,
            'user_id' => $user->id
        ]);

             // ✅ CORRECTION : Ajout de logs pour le debug des relations
            Log::info('🔍 Recherche conjoint', [
            'user_type' => $userType,
            'user_id' => $user->id,
            'query_type' => $isAgent ? 'agent_id' : 'retraite_id'
            ]);
            
            // Récupérer le conjoint actif selon le type d'utilisateur
            $conjoint = $isAgent 
                ? Conjoint::where('agent_id', $user->id)->where('statut', 'ACTIF')->first()
                : Conjoint::where('retraite_id', $user->id)->where('statut', 'ACTIF')->first();

                Log::info('📋 Résultat recherche conjoint', [
            'conjoint_found' => $conjoint ? 'OUI' : 'NON',
            'conjoint_id' => $conjoint->id ?? null
        ]);

         Log::info('🔍 Recherche enfants', [
            'user_type' => $userType,
            'user_id' => $user->id,
            'query_type' => $isAgent ? 'agent_id' : 'retraite_id'
        ]);

            // Récupérer les enfants actifs selon le type d'utilisateur  
        $enfants = $isAgent
            ? \App\Models\Enfant::where('agent_id', $user->id)->where('actif', true)->orderBy('date_naissance', 'desc')->get()
            : \App\Models\Enfant::where('retraite_id', $user->id)->where('actif', true)->orderBy('date_naissance', 'desc')->get();

        Log::info('📋 Résultat recherche enfants', [
            'enfants_count' => $enfants->count(),
            'enfants_ids' => $enfants->pluck('id')->toArray()
        ]);

            // Statistiques famille
            $stats = [
                'nombre_enfants' => $enfants->count(),
                'enfants_mineurs' => $enfants->filter(function($enfant) {
                    return Carbon::parse($enfant->date_naissance)->age < 18;
                })->count(),
                'enfants_avec_prestations' => $enfants->where('prestation_familiale', true)->count(),
                'conjoint_presente' => $conjoint ? true : false,
                'conjoint_travaille' => $conjoint && !empty($conjoint->matricule_conjoint)
            ];

            // Formater les données du conjoint
            $conjointData = null;
            if ($conjoint) {
                $conjointData = [
                    'id' => $conjoint->id,
                    'nom_complet' => trim($conjoint->prenoms . ' ' . $conjoint->nom),
                    'nom' => $conjoint->nom,
                    'prenoms' => $conjoint->prenoms,
                    'sexe' => $conjoint->sexe,
                    'age' => Carbon::parse($conjoint->date_naissance)->age,
                    'date_naissance' => $conjoint->date_naissance,
                    'date_mariage' => $conjoint->date_mariage,
                    'travaille' => !empty($conjoint->matricule_conjoint),
                    'identifiant' => $conjoint->matricule_conjoint ?: $conjoint->nag_conjoint,
                    'profession' => $conjoint->profession,
                    'statut' => $conjoint->statut
                ];
            }

            // Formater les données des enfants
            $enfantsData = $enfants->map(function ($enfant) {
                $age = Carbon::parse($enfant->date_naissance)->age;
                return [
                    'id' => $enfant->id,
                    'enfant_id' => $enfant->enfant_id,
                    'nom_complet' => trim($enfant->prenoms . ' ' . $enfant->nom),
                    'nom' => $enfant->nom,
                    'prenoms' => $enfant->prenoms,
                    'sexe' => $enfant->sexe,
                    'age' => $age,
                    'date_naissance' => $enfant->date_naissance,
                    'prestation_familiale' => $enfant->prestation_familiale,
                    'scolarise' => $enfant->scolarise,
                    'niveau_scolaire' => $enfant->niveau_scolaire,
                    'est_mineur' => $age < 18,
                    'en_age_scolarite' => $age >= 3 && $age <= 25,
                    'prestations_actives' => 0
                ];
            });


            // ✅ CORRECTION : Ajout du sexe par défaut pour les retraités
            $userSexe = $user->sexe ?? 'M'; // Valeur par défaut si pas défini
            $userSituation = $user->situation_matrimoniale ?? 'Non spécifiée';


            // Données de l'agent/retraité adaptées
            $agentData = [
                'id' => $user->id,
                'nom_complet' => trim($user->prenoms . ' ' . $user->nom),
                'matricule' => $isAgent ? $user->matricule_solde : $user->numero_pension,
                'sexe' => $user->sexe ?? 'M',
                'situation_matrimoniale' => $user->situation_matrimoniale ?? 'Non spécifiée',
                'type' => $userType
            ];

            Log::info('✅ Données grappe familiale assemblées', [
            'agent_data' => $agentData,
            'conjoint_presente' => $stats['conjoint_presente'],
            'nombre_enfants' => $stats['nombre_enfants'],
            'stats' => $stats
        ]);
            return response()->json([
                'success' => true,
                'grappe_familiale' => [
                    'agent' => $agentData,
                    'conjoint' => $conjointData,
                    'enfants' => $enfantsData,
                    'statistiques' => $stats
                ]
            ]);

       } catch (\Exception $e) {
        Log::error('❌ Erreur récupération grappe familiale:', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $request->user()->id ?? 'unknown',
            'user_class' => get_class($request->user())
        ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données familiales'
            ], 500);
        }
    }

    /**
     * Sauvegarder conjoint pour agent OU retraité
     */
    public function saveConjoint(Request $request)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserAccess($user);
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
            $data['statut'] = 'ACTIF';
            
            // Déterminer le type d'utilisateur
            $isAgent = $user instanceof Agent;
            if ($isAgent) {
                $data['agent_id'] = $user->id;
                $data['retraite_id'] = null;
            } else {
                $data['retraite_id'] = $user->id;
                $data['agent_id'] = null;
            }

            // Vérifier s'il y a déjà un conjoint actif
            $conjoint = $isAgent 
                ? Conjoint::where('agent_id', $user->id)->where('statut', 'ACTIF')->first()
                : Conjoint::where('retraite_id', $user->id)->where('statut', 'ACTIF')->first();

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
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du conjoint'
            ], 500);
        }
    }

    /**
     * Ajouter enfant pour agent OU retraité
     */
    public function addEnfant(Request $request)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserAccess($user);
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
            $data['actif'] = true;
            
            // Déterminer le type d'utilisateur
            $isAgent = $user instanceof Agent;
            if ($isAgent) {
                $data['agent_id'] = $user->id;
                $data['retraite_id'] = null;
                $data['matricule_parent'] = substr($user->matricule_solde, 0, -1);
            } else {
                $data['retraite_id'] = $user->id;
                $data['agent_id'] = null;
                $data['matricule_parent'] = $user->numero_pension;
            }

            // Vérifier si l'enfant n'existe pas déjà
            $existant = Enfant::where('enfant_id', $data['enfant_id'])
                ->where(function($query) use ($user, $isAgent) {
                    if ($isAgent) {
                        $query->where('agent_id', $user->id);
                    } else {
                        $query->where('retraite_id', $user->id);
                    }
                })
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
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown'
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
            
            $checkResult = $this->checkUserAccess($user);
            if ($checkResult) return $checkResult;

            // Trouver l'enfant selon le type d'utilisateur
            $isAgent = $user instanceof Agent;
            $enfant = Enfant::where('id', $id)
                ->where(function($query) use ($user, $isAgent) {
                    if ($isAgent) {
                        $query->where('agent_id', $user->id);
                    } else {
                        $query->where('retraite_id', $user->id);
                    }
                })
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
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'enfant'
            ], 500);
        }
    }

    /**
     * Supprimer un enfant
     */
    public function deleteEnfant(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $checkResult = $this->checkUserAccess($user);
            if ($checkResult) return $checkResult;

            // Trouver l'enfant selon le type d'utilisateur
            $isAgent = $user instanceof Agent;
            $enfant = Enfant::where('id', $id)
                ->where(function($query) use ($user, $isAgent) {
                    if ($isAgent) {
                        $query->where('agent_id', $user->id);
                    } else {
                        $query->where('retraite_id', $user->id);
                    }
                })
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
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'enfant'
            ], 500);
        }
    }
}