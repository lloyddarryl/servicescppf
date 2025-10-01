<?php
// database/seeders/CarrieresTestSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\Carriere;
use Carbon\Carbon;

class CarrieresTestSeeder extends Seeder
{
    public function run(): void
    {
        // Prendre le premier agent disponible
        $agent = Agent::first();
        
        if (!$agent) {
            $this->command->error("Aucun agent trouvé !");
            return;
        }
        
        $this->command->info("Test avec agent : " . $agent->matricule_solde);
        
        // Créer UNE carrière de test avec des valeurs en dur
        $carriere = Carriere::create([
            'agent_id' => $agent->id,
            'matricule_solde' => $agent->matricule_solde,
            'numero_ordre' => 999,
            'date_debut' => Carbon::parse('2024-03-01'),
            'date_fin' => Carbon::parse('2024-03-31'),
            'position' => 'TEST POSITION',
            'etablissement' => 'TEST ETABLISSEMENT',
            'corps' => 'TEST CORPS',
            'grade' => 999,
            'indice' => 9999,
            'retenue' => 12345.67,
            'nombre_mois' => 1,
            'regime' => 1,
            'sous_regime' => 'TEST',
            'annuite' => 0.5,
            'total_cotisations' => 12345.67,
            'statut' => 'VALIDE',
            'observations' => 'TEST',
        ]);
        
        $this->command->info("Carrière créée avec ID : " . $carriere->id);
        
        // Relire depuis la BDD pour vérifier
        $verif = Carriere::find($carriere->id);
        
        $this->command->info("\n=== VÉRIFICATION ===");
        $this->command->info("Etablissement : " . ($verif->etablissement ?? 'NULL'));
        $this->command->info("Corps : " . ($verif->corps ?? 'NULL'));
        $this->command->info("Grade : " . ($verif->grade ?? 'NULL'));
        $this->command->info("Indice : " . ($verif->indice ?? 'NULL'));
        $this->command->info("Retenue : " . ($verif->retenue ?? 'NULL'));
    }
}