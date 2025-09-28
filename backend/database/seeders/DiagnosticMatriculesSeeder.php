<?php
// database/seeders/DiagnosticMatriculesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\Retraite;

class DiagnosticMatriculesSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/enfants_fur.xlsx');
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Échantillon de 20 matricules du fichier
        $this->command->info("=== MATRICULES DANS LE FICHIER EXCEL ===");
        for ($row = 2; $row <= 21; $row++) {
            $nMattirP1 = $worksheet->getCell("H$row")->getValue();
            $cMattirP1 = $worksheet->getCell("I$row")->getValue();
            $matricule = trim($nMattirP1) . trim($cMattirP1);
            
            $this->command->info("Ligne $row: N_MATTIR_P1='$nMattirP1' | C_MATTIR_P1='$cMattirP1' | Résultat='$matricule'");
        }
        
        // Échantillon de 10 matricules de la BDD
        $this->command->info("\n=== MATRICULES AGENTS DANS LA BDD ===");
        Agent::limit(10)->get(['matricule_solde'])->each(function($agent) {
            $this->command->info("Agent: '{$agent->matricule_solde}'");
        });
        
        $this->command->info("\n=== MATRICULES RETRAITÉS DANS LA BDD ===");
        Retraite::limit(10)->get(['numero_pension'])->each(function($retraite) {
            $this->command->info("Retraité: '{$retraite->numero_pension}'");
        });
        
        $spreadsheet->disconnectWorksheets();
    }
}