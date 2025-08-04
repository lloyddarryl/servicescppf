<?php
// File: app/Http/Controllers/PensionTestController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Agent;
use App\Models\ParametrePension;
use App\Models\CoefficientTemporel;
use App\Models\SimulationPension;

class PensionTestController extends Controller
{
    /**
     * Test de diagnostic complet du simulateur
     */
    public function diagnostic(Request $request)
    {
        try {
            $user = $request->user();
            $diagnostics = [];

            // 1. Vérifier l'utilisateur
            $diagnostics['user'] = [
                'authenticated' => $user ? true : false,
                'type' => $user ? get_class($user) : null,
                'id' => $user ? $user->id : null,
                'is_agent' => $user instanceof Agent,
            ];

            // 2. Vérifier les données utilisateur
            if ($user instanceof Agent) {
                $diagnostics['user_data'] = [
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'matricule' => $user->matricule_solde,
                    'date_naissance' => $user->date_naissance,
                    'date_prise_service' => $user->date_prise_service,
                    'indice' => $user->indice,
                    'direction' => $user->direction,
                    'grade' => $user->grade,
                ];
            }

            // 3. Vérifier les tables
            $diagnostics['tables'] = [
                'agents_exists' => DB::getSchemaBuilder()->hasTable('agents'),
                'simulations_pension_exists' => DB::getSchemaBuilder()->hasTable('simulations_pension'),
                'parametres_pension_exists' => DB::getSchemaBuilder()->hasTable('parametres_pension'),
                'coefficients_temporels_exists' => DB::getSchemaBuilder()->hasTable('coefficients_temporels'),
            ];

            // 4. Vérifier les paramètres avec vos méthodes
            $diagnostics['parametres'] = [
                'count' => ParametrePension::count(),
                'active_count' => ParametrePension::active()->count(),
                'age_retraite' => ParametrePension::getValeur('AGE_RETRAITE'),
                'taux_liquidation' => ParametrePension::getValeur('TAUX_LIQUIDATION_ANNUEL'),
                'valeur_point' => ParametrePension::getValeur('VALEUR_POINT_INDICE'),
                'bonif_conjoint' => ParametrePension::getValeur('BONIF_CONJOINT'),
            ];

            // 5. Vérifier les coefficients
            $diagnostics['coefficients'] = [
                'count' => CoefficientTemporel::count(),
                'coeff_2025' => CoefficientTemporel::getCoefficient(2025),
                'coeff_2029' => CoefficientTemporel::getCoefficient(2029),
            ];

            // 6. Test de simulation basique
            if ($user instanceof Agent) {
                try {
                    $testSimulation = $this->testSimulation($user);
                    $diagnostics['test_simulation'] = $testSimulation;
                } catch (\Exception $e) {
                    $diagnostics['test_simulation'] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // 7. Vérifier les colonnes de la table agents
            if (DB::getSchemaBuilder()->hasTable('agents')) {
                $columns = DB::getSchemaBuilder()->getColumnListing('agents');
                $diagnostics['agent_columns'] = [
                    'has_indice' => in_array('indice', $columns),
                    'has_date_naissance' => in_array('date_naissance', $columns),
                    'has_date_prise_service' => in_array('date_prise_service', $columns),
                    'has_situation_matrimoniale' => in_array('situation_matrimoniale', $columns),
                    'all_columns' => $columns
                ];
            }

            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics,
                'timestamp' => now(),
                'message' => 'Diagnostic complet effectué'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur diagnostic:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Test de simulation simple
     */
    private function testSimulation($user)
    {
        $indice = $user->indice ?? 1001;
        $salaire = $indice * 500;
        $dureeService = 20; // Test avec 20 ans
        $tauxLiquidation = $dureeService * 1.8;
        $pensionBase = ($salaire * $tauxLiquidation) / 100;
        $coefficientTemporel = CoefficientTemporel::getCoefficient(2025);
        $pensionFinale = ($pensionBase * $coefficientTemporel) / 100;

        return [
            'success' => true,
            'indice' => $indice,
            'salaire_reference' => $salaire,
            'duree_service' => $dureeService,
            'taux_liquidation' => $tauxLiquidation,
            'pension_base' => $pensionBase,
            'coefficient_temporel' => $coefficientTemporel,
            'pension_finale' => $pensionFinale
        ];
    }

    /**
     * Nettoyer les données de test
     */
    public function cleanup(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!($user instanceof Agent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux agents'
                ], 403);
            }

            // Supprimer les simulations de test
            $deleted = SimulationPension::where('agent_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => "Nettoyage effectué: $deleted simulations supprimées"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialiser les données de test adaptées à vos modèles
     */
    public function initTestData()
    {
        try {
            // Initialiser quelques paramètres de base avec vos conventions
            $parametres = [
                [
                    'code_parametre' => 'AGE_RETRAITE', 
                    'libelle' => 'Âge de départ à la retraite',
                    'valeur' => 60,
                    'type_valeur' => 'integer'
                ],
                [
                    'code_parametre' => 'TAUX_LIQUIDATION_ANNUEL', 
                    'libelle' => 'Taux de liquidation par année',
                    'valeur' => 1.8,
                    'type_valeur' => 'decimal'
                ],
                [
                    'code_parametre' => 'VALEUR_POINT_INDICE', 
                    'libelle' => 'Valeur du point d\'indice',
                    'valeur' => 500,
                    'type_valeur' => 'decimal'
                ],
            ];

            foreach ($parametres as $param) {
                ParametrePension::updateOrCreate(
                    ['code_parametre' => $param['code_parametre']],
                    array_merge($param, [
                        'description' => 'Paramètre de test',
                        'date_effet' => now(),
                        'is_active' => true
                    ])
                );
            }

            // Initialiser quelques coefficients
            CoefficientTemporel::initCoefficients();

            return response()->json([
                'success' => true,
                'message' => 'Données de test initialisées avec vos modèles'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}