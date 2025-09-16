<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Migration pour transférer les données des agents vers la table carrieres
 * 
 * Ce script migre les informations de cotisations existantes depuis la table agents
 * vers la nouvelle table carrieres en créant une carrière par défaut pour chaque agent
 */
class MigrationDonneesAgentsVersCarrieres extends Migration
{
    /**
     * Exécuter la migration des données
     */
    public function up()
    {
        // Récupérer tous les agents actifs avec leurs données de cotisations
        $agents = DB::table('agents')
            ->where('is_active', true)
            ->where('is_radie', false)
            ->get();

        $totalAgents = count($agents);
        $migrationsReussies = 0;

        echo "Début de la migration des données pour {$totalAgents} agents...\n";

        foreach ($agents as $agent) {
            try {
                // Créer une carrière par défaut basée sur les données actuelles de l'agent
                DB::table('carrieres')->insert([
                    'agent_id' => $agent->id,
                    'numero_ordre' => 1, // Premier numéro d'ordre
                    'date_debut' => $agent->date_prise_service ?: Carbon::now()->subYears(1),
                    'date_fin' => null, // Carrière en cours
                    'position' => 'ACTIVITE', // Position par défaut
                    'etablissement' => $agent->etablissement ?: 'Direction des Systèmes d\'Information',
                    'corps' => $agent->corps ?: 'FONCTIONNAIRES',
                    'grade' => $agent->grade ?: 100,
                    'indice' => $agent->indice ?: 1000,
                    'retenue' => $agent->retenue_mensuelle ?: 0,
                    'nombre_mois' => $agent->duree_service_mois ?: $this->calculerNombreMois($agent->date_prise_service),
                    'regime' => $agent->regime ?: 1,
                    'sous_regime' => $agent->sous_regime ?: 'General',
                    'annuite' => $agent->duree_service_mois ? round($agent->duree_service_mois / 12, 4) : 0,
                    'total_cotisations' => ($agent->retenue_mensuelle ?: 0) * ($agent->duree_service_mois ?: $this->calculerNombreMois($agent->date_prise_service)), // Ajout du total des cotisations
                    'statut' => 'VALIDE', // Par défaut, considérer comme validée
                    'observations' => 'Données migrées depuis la table agents - ' . now()->format('d/m/Y H:i'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $migrationsReussies++;
                echo "✓ Agent {$agent->matricule_solde} migré avec succès\n";

            } catch (\Exception $e) {
                echo "✗ Erreur pour l'agent {$agent->matricule_solde}: {$e->getMessage()}\n";
            }
        }

        echo "\nMigration terminée:\n";
        echo "- Total agents traités: {$totalAgents}\n";
        echo "- Migrations réussies: {$migrationsReussies}\n";
        echo "- Erreurs: " . ($totalAgents - $migrationsReussies) . "\n";
    }

    /**
     * Annuler la migration
     */
    public function down()
    {
        // Supprimer toutes les carrières créées par cette migration
        $deleted = DB::table('carrieres')
            ->where('observations', 'LIKE', 'Données migrées depuis la table agents%')
            ->delete();

        echo "Migration annulée: {$deleted} carrières supprimées\n";
    }

    /**
     * Calculer le nombre de mois depuis la date de prise de service
     */
    private function calculerNombreMois($datePriseService)
    {
        if (!$datePriseService) {
            return 0;
        }

        $dateDebut = Carbon::parse($datePriseService);
        $maintenant = Carbon::now();
        
        return $dateDebut->diffInMonths($maintenant);
    }
}