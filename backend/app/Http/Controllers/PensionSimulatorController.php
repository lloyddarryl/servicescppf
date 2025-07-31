<?php
// File: app/Http/Controllers/PensionSimulatorController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\CarriereHistorique;
use App\Models\GrilleIndiciaire;
use App\Models\ParametrePension;
use App\Models\SimulationPension;
use Carbon\Carbon;

class PensionSimulatorController extends Controller
{
    /**
     * Obtenir les données du profil pour le simulateur
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'actif' : 'retraite';
            
            $profileData = [
                'id' => $user->id,
                'type' => $userType,
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
                'matricule' => $userType === 'actif' ? $user->matricule_solde : $user->numero_pension,
                'dateNaissance' => $userType === 'actif' ? $this->estimateBirthDate($user) : $user->date_naissance,
                'situationMatrimoniale' => $user->situation_matrimoniale ?? 'Non spécifiée',
                'sexe' => $user->sexe ?? 'M',
                'dateEmbauche' => $userType === 'actif' ? $user->date_prise_service : $this->estimateStartDate($user),
                'posteActuel' => $userType === 'actif' ? $user->poste : $user->ancien_poste,
                'direction' => $userType === 'actif' ? $user->direction : $user->ancienne_direction,
                'grade' => $userType === 'actif' ? $user->grade : 'Grade retraité',
                'indice' => $this->getCurrentIndice($user, $userType),
                'corps' => $user->corps ?? 'FONCTIONNAIRES',
                'etablissement' => $user->etablissement ?? $user->direction ?? $user->ancienne_direction,
                'statut' => $userType === 'actif' ? 'FONCTIONNAIRES' : 'RETRAITE'
            ];

            return response()->json([
                'success' => true,
                'profile' => $profileData
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération profil simulateur:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil'
            ], 500);
        }
    }

    /**
     * Simuler la pension
     */
    public function simulatePension(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'actif' : 'retraite';

            if ($userType === 'retraite') {
                return $this->getExistingPension($user);
            }

            // Récupérer les paramètres de simulation
            $indiceSimule = $request->get('indice', $this->getCurrentIndice($user, $userType));
            $dateRetraiteCustom = $request->get('date_retraite');
            
            // Calculer les données de base
            $dateNaissance = $this->estimateBirthDate($user);
            $dateEmbauche = $user->date_prise_service;
            $age = Carbon::parse($dateNaissance)->age;
            
            // Date de retraite (60 ans par défaut)
            $dateRetraite = $dateRetraiteCustom 
                ? Carbon::parse($dateRetraiteCustom)
                : Carbon::parse($dateNaissance)->addYears(60);

            // Calculer la durée de service
            $dureeServiceActuelle = Carbon::parse($dateEmbauche)->diffInYears(now());
            $dureeServiceRetraite = Carbon::parse($dateEmbauche)->diffInYears($dateRetraite);
            
            // Calcul selon l'Article 94
            $salaireReference = $this->calculateSalaire($indiceSimule);
            
            // Taux de liquidation : années × 1,8%
            $tauxLiquidation = $this->calculateTauxLiquidation($dureeServiceRetraite);
            
            // Pension de base
            $pensionBase = ($salaireReference * $tauxLiquidation) / 100;
            
            // Coefficient temporel selon l'année de départ
            $anneePension = $dateRetraite->year;
            $coefficientTemporel = $this->getCoefficientTemporel($anneePension);
            
            // Pension après coefficient temporel
            $pensionApresCoeff = ($pensionBase * $coefficientTemporel) / 100;
            
            // Bonifications
            $bonifications = $this->calculateBonifications($user, $pensionApresCoeff);
            
            // Pension totale finale
            $pensionTotale = $pensionApresCoeff + $bonifications;

            // Sauvegarder la simulation avec les nouveaux paramètres
            $simulation = SimulationPension::create([
                'agent_id' => $user->id,
                'date_simulation' => now(),
                'date_retraite_prevue' => $dateRetraite,
                'duree_service_simulee' => $dureeServiceRetraite,
                'indice_simule' => $indiceSimule,
                'salaire_reference' => $salaireReference,
                'taux_liquidation' => $tauxLiquidation,
                'pension_base' => $pensionBase,
                'bonifications' => $bonifications,
                'pension_totale' => $pensionTotale,
                'parametres_utilises' => json_encode([
                    'methode_calcul' => 'Article_94',
                    'formule_taux' => 'annees_x_1.8',
                    'coefficient_temporel' => $coefficientTemporel,
                    'annee_pension' => $anneePension,
                    'pension_apres_coefficient' => $pensionApresCoeff
                ])
            ]);

            $simulationData = [
                'dateRetraitePrevisionnelle' => $dateRetraite->format('d/m/Y'),
                'ageRetraite' => 60,
                'ageActuel' => $age,
                'anneesRestantes' => max(0, 60 - $age),
                'dureeServiceActuelle' => $dureeServiceActuelle,
                'dureeServiceRetraite' => $dureeServiceRetraite,
                'salaireActuel' => $this->calculateSalaire($this->getCurrentIndice($user, $userType)),
                'salaireReference' => $salaireReference,
                'indiceActuel' => $this->getCurrentIndice($user, $userType),
                'indiceSimule' => $indiceSimule,
                'tauxLiquidation' => round($tauxLiquidation, 2),
                'pensionBase' => round($pensionBase),
                'coefficientTemporel' => $coefficientTemporel,
                'pensionApresCoefficient' => round($pensionApresCoeff),
                'bonifications' => round($bonifications),
                'pensionTotale' => round($pensionTotale),
                'eligible' => $dureeServiceRetraite >= 15,
                'simulationId' => $simulation->id,
                'anneePension' => $anneePension,
                'methodeCalcul' => 'Article 94 - Années × 1,8%'
            ];

            return response()->json([
                'success' => true,
                'simulation' => $simulationData,
                'message' => 'Simulation calculée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur simulation pension:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation'
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des simulations
     */
    public function getSimulationHistory(Request $request)
    {
        try {
            $user = $request->user();
            
            $simulations = SimulationPension::where('agent_id', $user->id)
                ->orderBy('date_simulation', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($sim) {
                    return [
                        'id' => $sim->id,
                        'date' => $sim->date_simulation->format('d/m/Y H:i'),
                        'dateRetraite' => $sim->date_retraite_prevue->format('d/m/Y'),
                        'dureeService' => $sim->duree_service_simulee,
                        'indice' => $sim->indice_simule,
                        'pensionTotale' => $sim->pension_totale,
                        'tauxLiquidation' => $sim->taux_liquidation
                    ];
                });

            return response()->json([
                'success' => true,
                'simulations' => $simulations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Obtenir les paramètres de pension selon l'Article 94
     */
    public function getParameters()
    {
        try {
            $parametres = [
                'age_retraite' => 60,
                'duree_service_minimum' => 15,
                'formule_taux_liquidation' => 'annees × 1.8%',
                'formule_solde_base' => 'indice × 500',
                'formule_pension_base' => 'solde_base × taux_liquidation',
                'coefficients_temporels' => [
                    2024 => 89, 2025 => 91, 2026 => 94, 2027 => 96, 
                    2028 => 98, 2029 => 100
                ],
                'bonification_conjoint' => 0.03,
                'bonification_enfant' => 0.02,
                'article_reference' => 'Article 94 du décret'
            ];

            return response()->json([
                'success' => true,
                'parametres' => $parametres
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paramètres'
            ], 500);
        }
    }

    /**
     * Calculer le salaire selon l'indice
     */
    private function calculateSalaire($indice)
    {
        return $indice * 500; // Formule de base : indice × 500
    }

    /**
     * Calculer le taux de liquidation selon l'Article 94
     */
    private function calculateTauxLiquidation($dureeService)
    {
        if ($dureeService < 15) {
            return 0; // Pas de pension (minimum 15 ans)
        }

        // NOUVELLE RÈGLE : Nombre d'années × 1,8%
        return $dureeService * 1.8;
    }

    /**
     * Obtenir le coefficient temporel selon l'Article 94
     */
    private function getCoefficientTemporel($anneePension)
    {
        // Coefficients selon l'Article 94
        $coefficients = [
            2015 => 70, 2016 => 72, 2017 => 74, 2018 => 76,
            2019 => 79, 2020 => 81, 2021 => 83, 2022 => 85,
            2023 => 87, 2024 => 89, 2025 => 91, 2026 => 94,
            2027 => 96, 2028 => 98
        ];

        // À partir de 2029 : 100%
        if ($anneePension >= 2029) {
            return 100;
        }

        return $coefficients[$anneePension] ?? 100;
    }

    /**
     * Calculer les bonifications
     */
    private function calculateBonifications($user, $pensionBase)
    {
        $bonifications = 0;

        // Bonification pour situation matrimoniale
        if ($user->situation_matrimoniale === 'Marié(e)') {
            $bonifications += $pensionBase * 0.03; // 3% pour conjoint
        }

        // Autres bonifications possibles
        // (enfants, services exceptionnels, etc.)

        return $bonifications;
    }

    /**
     * Obtenir l'indice actuel
     */
    private function getCurrentIndice($user, $userType)
    {
        if ($userType === 'actif') {
            return $user->indice ?? 1001; // Valeur par défaut
        } else {
            return $user->indice_retraite ?? 800; // Valeur par défaut retraité
        }
    }

    /**
     * Estimer la date de naissance (si non disponible)
     */
    private function estimateBirthDate($user)
    {
        // Si on a la date de prise de service, estimer l'âge à l'embauche (25 ans)
        return Carbon::parse($user->date_prise_service)->subYears(25)->format('Y-m-d');
    }

    /**
     * Estimer la date de début de service pour retraités
     */
    private function estimateStartDate($user)
    {
        // Estimer 35 ans de service
        return Carbon::parse($user->date_retraite)->subYears(35)->format('Y-m-d');
    }

    /**
     * Obtenir les données de pension existante pour les retraités
     */
    private function getExistingPension($user)
    {
        $dateRetraite = Carbon::parse($user->date_retraite);
        $age = Carbon::parse($user->date_naissance)->age;
        $dureeService = $this->estimateServiceDuration($user);

        return response()->json([
            'success' => true,
            'simulation' => [
                'dateRetraitePrevisionnelle' => $dateRetraite->format('d/m/Y'),
                'ageRetraite' => $dateRetraite->diffInYears($user->date_naissance),
                'ageActuel' => $age,
                'anneesRestantes' => 0,
                'dureeServiceActuelle' => $dureeService,
                'dureeServiceRetraite' => $dureeService,
                'salaireActuel' => 0,
                'salaireReference' => $this->estimateReferenceSalary($user),
                'indiceActuel' => $user->indice_retraite ?? 800,
                'indiceSimule' => $user->indice_retraite ?? 800,
                'tauxLiquidation' => $this->calculateTauxLiquidation($dureeService),
                'pensionBase' => $user->montant_pension,
                'bonifications' => 0,
                'pensionTotale' => $user->montant_pension,
                'eligible' => true,
                'isRetraite' => true
            ],
            'message' => 'Données de pension existante'
        ]);
    }

    /**
     * Estimer la durée de service pour un retraité
     */
    private function estimateServiceDuration($user)
    {
        $startDate = $this->estimateStartDate($user);
        return Carbon::parse($startDate)->diffInYears($user->date_retraite);
    }

    /**
     * Estimer le salaire de référence pour un retraité
     */
    private function estimateReferenceSalary($user)
    {
        $indice = $user->indice_retraite ?? 800;
        return $this->calculateSalaire($indice);
    }
}