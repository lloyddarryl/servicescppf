<?php
// File: app/Console/Commands/InitPensionSimulator.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParametrePension;
use App\Models\CoefficientTemporel;
use App\Models\Agent;

class InitPensionSimulator extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pension:init {--force : Force la réinitialisation}';

    /**
     * The console command description.
     */
    protected $description = 'Initialise le simulateur de pension CPPF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Initialisation du simulateur de pension CPPF...');

        try {
            // 1. Vérifier les prérequis
            $this->checkPrerequisites();

            // 2. Initialiser les paramètres
            $this->initParametres();

            // 3. Initialiser les coefficients temporels
            $this->initCoefficients();

            // 4. Vérifier quelques agents
            $this->checkAgents();

            // 5. Test rapide
            $this->testSimulation();

            $this->info('✅ Simulateur de pension initialisé avec succès !');
            $this->info('🔗 Vous pouvez maintenant tester : /api/actifs/simulateur-pension/profil');

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'initialisation : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function checkPrerequisites()
    {
        $this->info('📋 Vérification des prérequis...');

        // Vérifier les tables
        $tables = [
            'agents' => \Schema::hasTable('agents'),
            'parametres_pension' => \Schema::hasTable('parametres_pension'),
            'coefficients_temporels' => \Schema::hasTable('coefficients_temporels'),
            'simulations_pension' => \Schema::hasTable('simulations_pension'),
        ];

        foreach ($tables as $table => $exists) {
            if ($exists) {
                $this->line("   ✓ Table $table : OK");
            } else {
                $this->error("   ❌ Table $table : MANQUANTE");
                throw new \Exception("Table $table manquante. Exécutez les migrations.");
            }
        }
    }

    private function initParametres()
    {
        $this->info('📊 Initialisation des paramètres de pension...');

        $parametres = [
            [
                'code_parametre' => 'AGE_RETRAITE',
                'libelle' => 'Âge de départ à la retraite',
                'valeur' => 60,
                'type_valeur' => 'integer',
                'description' => 'Âge légal de départ à la retraite pour les fonctionnaires'
            ],
            [
                'code_parametre' => 'DUREE_SERVICE_MIN',
                'libelle' => 'Durée de service minimum',
                'valeur' => 15,
                'type_valeur' => 'integer',
                'description' => 'Durée minimum de service pour avoir droit à une pension'
            ],
            [
                'code_parametre' => 'TAUX_LIQUIDATION_ANNUEL',
                'libelle' => 'Taux de liquidation par année',
                'valeur' => 1.8,
                'type_valeur' => 'decimal',
                'description' => 'Taux de liquidation par année de service (années × 1,8%)'
            ],
            [
                'code_parametre' => 'VALEUR_POINT_INDICE',
                'libelle' => 'Valeur du point d\'indice',
                'valeur' => 500,
                'type_valeur' => 'decimal',
                'description' => 'Valeur du point d\'indice en FCFA'
            ],
            [
                'code_parametre' => 'TAUX_LIQUIDATION_MAX',
                'libelle' => 'Taux de liquidation maximum',
                'valeur' => 75.0,
                'type_valeur' => 'decimal',
                'description' => 'Taux maximum de liquidation (75%)'
            ],
            [
                'code_parametre' => 'BONIF_CONJOINT',
                'libelle' => 'Bonification pour conjoint',
                'valeur' => 3.0,
                'type_valeur' => 'decimal',
                'description' => 'Majoration pour conjoint à charge (3%)'
            ],
            [
                'code_parametre' => 'BONIF_ENFANT',
                'libelle' => 'Bonification par enfant',
                'valeur' => 2.0,
                'type_valeur' => 'decimal',
                'description' => 'Majoration par enfant à charge (2%)'
            ]
        ];

        $created = 0;
        $updated = 0;

        foreach ($parametres as $param) {
            $existing = ParametrePension::where('code_parametre', $param['code_parametre'])->first();
            
            if ($existing && !$this->option('force')) {
                $this->line("   → {$param['code_parametre']} : existe déjà");
                continue;
            }

            ParametrePension::updateOrCreate(
                ['code_parametre' => $param['code_parametre']],
                array_merge($param, [
                    'is_active' => true,
                    'date_effet' => now(),
                    'date_fin' => null
                ])
            );

            if ($existing) {
                $updated++;
                $this->line("   ✓ {$param['code_parametre']} : mis à jour");
            } else {
                $created++;
                $this->line("   ✓ {$param['code_parametre']} : créé");
            }
        }

        $this->info("   📊 $created paramètres créés, $updated mis à jour");
    }

    private function initCoefficients()
    {
        $this->info('📈 Initialisation des coefficients temporels...');

        if (CoefficientTemporel::count() > 0 && !$this->option('force')) {
            $count = CoefficientTemporel::count();
            $this->line("   → $count coefficients existent déjà");
            return;
        }

        CoefficientTemporel::initCoefficients();
        $count = CoefficientTemporel::count();
        $this->info("   ✓ $count coefficients temporels initialisés");
    }

    private function checkAgents()
    {
        $this->info('👥 Vérification des agents...');

        $totalAgents = Agent::count();
        $agentsWithIndice = Agent::whereNotNull('indice')->count();
        $agentsWithDates = Agent::whereNotNull('date_naissance')
            ->whereNotNull('date_prise_service')
            ->count();

        $this->line("   📊 Total agents : $totalAgents");
        $this->line("   📊 Avec indice : $agentsWithIndice");
        $this->line("   📊 Avec dates complètes : $agentsWithDates");

        if ($agentsWithDates < $totalAgents) {
            $this->warn("   ⚠️  Certains agents n'ont pas toutes les données requises");
        }
    }

    private function testSimulation()
    {
        $this->info('🧪 Test de simulation...');

        try {
            // Test avec des valeurs par défaut
            $indice = 1001;
            $dureeService = 20;
            $salaire = ParametrePension::getValeur('VALEUR_POINT_INDICE') * $indice;
            $tauxLiquidation = $dureeService * ParametrePension::getValeur('TAUX_LIQUIDATION_ANNUEL');
            $pensionBase = ($salaire * $tauxLiquidation) / 100;
            $coefficient = CoefficientTemporel::getCoefficient(2025);
            $pensionFinale = ($pensionBase * $coefficient) / 100;

            $this->line("   ✓ Indice : $indice");
            $this->line("   ✓ Salaire de référence : " . number_format($salaire, 0, ',', ' ') . " FCFA");
            $this->line("   ✓ Taux de liquidation : $tauxLiquidation%");
            $this->line("   ✓ Pension de base : " . number_format($pensionBase, 0, ',', ' ') . " FCFA");
            $this->line("   ✓ Coefficient 2025 : $coefficient%");
            $this->line("   ✓ Pension finale : " . number_format($pensionFinale, 0, ',', ' ') . " FCFA");

        } catch (\Exception $e) {
            $this->error("   ❌ Erreur de test : " . $e->getMessage());
            throw $e;
        }
    }
}