<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\SimulationPension;
use App\Models\ParametrePension;
use App\Models\CoefficientTemporel;
use Carbon\Carbon;

class PensionSimulatorController extends Controller
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
        return null; // OK, continue
    }

    /**
     * Obtenir les données du profil pour le simulateur
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            // ✅ Vérifier que l'utilisateur est un agent
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            Log::info('User data in getProfile:', [
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'date_naissance' => $user->date_naissance,
                'date_prise_service' => $user->date_prise_service
            ]);

            // CORRECTION : Gestion plus souple des dates manquantes
            $dateNaissance = $user->date_naissance;
            $datePriseService = $user->date_prise_service;

            // Si les dates sont des strings, les convertir
            if (is_string($dateNaissance)) {
                try {
                    $dateNaissance = Carbon::parse($dateNaissance);
                } catch (\Exception $e) {
                    $dateNaissance = null;
                }
            }

            if (is_string($datePriseService)) {
                try {
                    $datePriseService = Carbon::parse($datePriseService);
                } catch (\Exception $e) {
                    $datePriseService = null;
                }
            }

            // Données par défaut si manquantes
            if (!$dateNaissance) {
                $dateNaissance = Carbon::now()->subYears(35); // Age par défaut 35 ans
                Log::warning('Date de naissance manquante, utilisation valeur par défaut', ['user_id' => $user->id]);
            }

            if (!$datePriseService) {
                $datePriseService = Carbon::now()->subYears(10); // 10 ans de service par défaut
                Log::warning('Date de prise de service manquante, utilisation valeur par défaut', ['user_id' => $user->id]);
            }
                        
            $profileData = [
                'id' => $user->id,
                'type' => 'actif',
                'nom' => $user->nom ?? 'Non renseigné',
                'prenoms' => $user->prenoms ?? 'Non renseigné',
                'matricule' => $user->matricule_solde ?? 'Non renseigné',
                'dateNaissance' => $dateNaissance->format('Y-m-d'),
                'situationMatrimoniale' => $user->situation_matrimoniale ?? 'Non spécifiée',
                'sexe' => $user->sexe ?? 'M',
                'dateEmbauche' => $datePriseService->format('Y-m-d'),
                'posteActuel' => $user->poste ?? 'Non renseigné',
                'direction' => $user->direction ?? 'Non renseignée',
                'grade' => $user->grade ?? 'Non renseigné',
                'indice' => $user->indice ?? 1001,
                'corps' => $user->corps ?? 'FONCTIONNAIRES',
                'etablissement' => $user->etablissement ?? ($user->direction ?? 'Non renseigné'),
                'statut' => 'FONCTIONNAIRE',
                'telephone' => $user->telephone ?? $user->phone ?? null,
                'email' => $user->email ?? null,
                'adresse' => $user->adresse ?? null,
                'lieu_naissance' => $user->lieu_naissance ?? null
            ];

            Log::info('Profile data:', ['profile' => $profileData]);

            return response()->json([
                'success' => true,
                'profile' => $profileData
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in getProfile:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculer la durée de service selon le principe d'annuité
     */
    private function calculateDureeServiceAnnuite($dateDebut, $dateFin)
    {
        $debut = Carbon::parse($dateDebut);
        $fin = Carbon::parse($dateFin);
        
        // Calculer les années et mois exacts
        $diffAnnees = $debut->diffInYears($fin);
        $diffMoisRestants = $debut->copy()->addYears($diffAnnees)->diffInMonths($fin);
        
        // Principe d'annuité
        if ($diffMoisRestants < 6) {
            // Moins de 6 mois → +0,5
            $dureeService = $diffAnnees + 0.5;
        } else {
            // 6 mois ou plus → année complète suivante
            $dureeService = $diffAnnees + 1;
        }
        
        return $dureeService;
    }

    /**
     * Formatage pour l'affichage (années + mois normaux)
     */
    private function formatDureeService($dureeEnAnnees) {
        $annees = floor($dureeEnAnnees);
        $mois = floor(($dureeEnAnnees - $annees) * 12);
        
        return [
            'annees' => $annees,
            'mois' => $mois,
            'total' => $dureeEnAnnees
        ];
    }

    /**
     * Simuler la pension
     */
    public function simulatePension(Request $request)
    {
        try {
            $user = $request->user();
            
            // ✅ Vérifier que l'utilisateur est un agent
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;

            // Récupérer les paramètres de simulation
            $indiceSimule = (int) ($request->get('indice', $user->indice ?? 1001));
            $dateRetraiteCustom = $request->get('date_retraite');
            
            // CORRECTION : Gestion plus robuste des dates
            $dateNaissance = $user->date_naissance;
            $dateEmbauche = $user->date_prise_service;

            // Conversion des dates si nécessaire
            if (is_string($dateNaissance)) {
                $dateNaissance = Carbon::parse($dateNaissance);
            } elseif (!$dateNaissance) {
                $dateNaissance = Carbon::now()->subYears(35);
            }

            if (is_string($dateEmbauche)) {
                $dateEmbauche = Carbon::parse($dateEmbauche);
            } elseif (!$dateEmbauche) {
                $dateEmbauche = Carbon::now()->subYears(10);
            }

            $age = $dateNaissance->age;
            
            // Date de retraite (60 ans par défaut)
            $dateRetraite = $dateRetraiteCustom 
                ? Carbon::parse($dateRetraiteCustom)
                : $dateNaissance->copy()->addYears(60);

            // ✅ NOUVEAU : Calculer la durée de service avec le principe d'annuité
            $dureeServiceActuelle = $this->calculateDureeServiceAnnuite($dateEmbauche, now());
            $dureeServiceRetraite = $this->calculateDureeServiceAnnuite($dateEmbauche, $dateRetraite);
            
            // Pour l'affichage normal (années + mois)
            $dureeServiceCalculee = $this->formatDureeService($dateEmbauche->diffInYears($dateRetraite, true));
            
            // Calcul selon l'Article 94 avec la durée d'annuité
            $salaireReference = $this->calculateSalaire($indiceSimule);
            
            // ✅ Taux de liquidation basé sur la durée d'annuité
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

            // Sauvegarder la simulation
            try {
                $simulation = SimulationPension::create([
                    'agent_id' => $user->id,
                    'date_simulation' => now(),
                    'date_retraite_prevue' => $dateRetraite,
                    'duree_service_simulee' => $dureeServiceRetraite, // ✅ Durée d'annuité pour les calculs
                    'indice_simule' => $indiceSimule,
                    'salaire_reference' => $salaireReference,
                    'taux_liquidation' => $tauxLiquidation,
                    'pension_base' => $pensionBase,
                    'bonifications' => $bonifications,
                    'pension_totale' => $pensionTotale,
                    'coefficient_temporel' => $coefficientTemporel,
                    'pension_apres_coefficient' => $pensionApresCoeff,
                    'annee_pension' => $anneePension,
                    'methode_calcul' => 'Article_94',
                    'created_at' => now(),
                    'parametres_utilises' => [
                        'formule_taux' => 'annees_x_1.8',
                        'coefficient_temporel' => $coefficientTemporel,
                        'annee_pension' => $anneePension,
                        'pension_apres_coefficient' => $pensionApresCoeff,
                        'principe_annuite' => true
                    ]
                ]);
                $simulationId = $simulation->id;
                
                Log::info('Simulation sauvegardée avec succès', [
                    'simulation_id' => $simulationId,
                    'agent_id' => $user->id,
                    'duree_annuite' => $dureeServiceRetraite,
                    'pension_totale' => $pensionTotale
                ]);
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la sauvegarde de la simulation:', [
                    'error' => $e->getMessage(),
                    'agent_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                $simulationId = null;
            }
            
            $simulationData = [
                'dateRetraitePrevisionnelle' => $dateRetraite->format('d/m/Y'),
                'ageRetraite' => 60,
                'ageActuel' => $age,
                'anneesRestantes' => max(0, 60 - $age),
                'dureeServiceActuelle' => $dureeServiceActuelle,
                'dureeServiceRetraite' => $dureeServiceCalculee['annees'], // ✅ Pour affichage
                'dureeServiceMois' => $dureeServiceCalculee['mois'], // ✅ Pour affichage
                'dureeServiceAnnuite' => $dureeServiceRetraite, // ✅ Pour calculs (principe d'annuité)
                'salaireActuel' => $this->calculateSalaire($user->indice ?? 1001),
                'salaireReference' => $salaireReference,
                'indiceActuel' => $user->indice ?? 1001,
                'indiceSimule' => $indiceSimule,
                'tauxLiquidation' => round($tauxLiquidation, 2),
                'pensionBase' => round($pensionBase),
                'coefficientTemporel' => $coefficientTemporel, // ✅ Pour affichage
                'pensionApresCoefficient' => round($pensionApresCoeff),
                'bonifications' => round($bonifications),
                'pensionTotale' => round($pensionTotale),
                'eligible' => $dureeServiceRetraite >= 15,
                'simulationId' => $simulationId,
                'anneePension' => $anneePension,
                'methodeCalcul' => 'Article 94 - Années × 1,8% (principe d\'annuité)'
            ];

            return response()->json([
                'success' => true,
                'simulation' => $simulationData,
                'message' => 'Simulation calculée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur simulation pension:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation: ' . $e->getMessage()
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
            
            // ✅ Vérifier que l'utilisateur est un agent
            $checkResult = $this->checkUserIsAgent($user);
            if ($checkResult) return $checkResult;
            
            // CORRECTION : Utiliser les scopes du modèle
            try {
                $simulations = SimulationPension::forAgent($user->id)
                    ->recent(10)
                    ->get()
                    ->map(function ($sim) {
                        return [
                            'id' => $sim->id,
                            'date' => $sim->created_at->format('d/m/Y H:i'),
                            'dateRetraite' => $sim->date_retraite_prevue->format('d/m/Y'),
                            'dureeService' => $sim->duree_service_simulee,
                            'indice' => $sim->indice_simule,
                            'pensionTotale' => $sim->pension_totale,
                            'tauxLiquidation' => $sim->taux_liquidation
                        ];
                    });
                    
                Log::info('Historique récupéré avec succès', [
                    'agent_id' => $user->id,
                    'count' => $simulations->count()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération de l\'historique:', [
                    'error' => $e->getMessage(),
                    'agent_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                $simulations = collect(); // Collection vide
            }

            return response()->json([
                'success' => true,
                'simulations' => $simulations
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur historique simulations:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
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
                'article_reference' => 'Article 94 du décret',
                'principe_annuite' => 'Moins de 6 mois = +0,5 an | 6 mois et plus = +1 an'
            ];

            return response()->json([
                'success' => true,
                'parametres' => $parametres
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur paramètres:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paramètres: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Calculer le salaire selon l'indice
     */
    private function calculateSalaire($indice)
    {
        return $indice * 500; // Formule de base : indice × 500
    }

    /**
     * Calculer le taux de liquidation selon l'Article 94 avec principe d'annuité
     */
    private function calculateTauxLiquidation($dureeService)
    {
        if ($dureeService < 15) {
            return 0; // Pas de pension (minimum 15 ans)
        }

        // ✅ Utilise maintenant la durée d'annuité pour le calcul
        $taux = $dureeService * 1.8;
        
        // Maximum 75%
        return min($taux, 75);
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
        if (isset($user->situation_matrimoniale) && $user->situation_matrimoniale === 'Marié(e)') {
            $tauxConjoint = ParametrePension::getValeur('BONIF_CONJOINT') ?? 3.0;
            $bonifications += $pensionBase * ($tauxConjoint / 100);
        }

        // Bonification pour enfants (si cette donnée est disponible)
        if (isset($user->nombre_enfants) && $user->nombre_enfants > 0) {
            $tauxEnfant = ParametrePension::getValeur('BONIF_ENFANT') ?? 2.0;
            $bonifications += $pensionBase * ($tauxEnfant / 100) * $user->nombre_enfants;
        }

        return $bonifications;
    }
}