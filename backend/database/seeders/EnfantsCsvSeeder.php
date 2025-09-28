<?php
// database/seeders/EnfantsCsvSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Enfant;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;

class EnfantsCsvSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = storage_path('app/imports/enfants_fur.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("Fichier CSV introuvable ! Convertis d'abord l'Excel.");
            return;
        }
        
        $this->command->info("Import CSV des enfants...");
        
        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // En-têtes
        
        // Compter les lignes
        $lineCount = 0;
        while (fgets($handle)) $lineCount++;
        rewind($handle);
        fgetcsv($handle); // Skip header
        
        $bar = $this->command->getOutput()->createProgressBar($lineCount);
        
        $batch = [];
        $batchSize = 500;
        $inserted = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            $enfantId = $row[0] ?? null;
            $nMattirP1 = trim($row[7] ?? '');
            $cMattirP1 = trim($row[8] ?? '');
            $matriculeParent = $nMattirP1 . $cMattirP1;
            
            $nom = trim($row[16] ?? '');
            $prenoms = trim($row[15] ?? '');
            $sexe = trim($row[18] ?? '');
            $dateNaissance = $row[19] ?? null;
            $prestationFamiliale = (int)($row[2] ?? 0);
            $allocationScolaire = (int)($row[4] ?? 0);
            
            if (empty($enfantId) || empty($matriculeParent)) {
                $bar->advance();
                continue;
            }
            
            // Chercher parent en cache
            static $parentsCache = [];
            
            if (!isset($parentsCache[$matriculeParent])) {
                $agent = Agent::where('matricule_solde', $matriculeParent)->first();
                $retraite = Retraite::where('numero_pension', $matriculeParent)->first();
                $parentsCache[$matriculeParent] = [
                    'agent_id' => $agent ? $agent->id : null,
                    'retraite_id' => $retraite ? $retraite->id : null
                ];
            }
            
            $parent = $parentsCache[$matriculeParent];
            
            if (!$parent['agent_id'] && !$parent['retraite_id']) {
                $bar->advance();
                continue;
            }
            
            $batch[] = [
                'enfant_id' => $enfantId,
                'matricule_parent' => $matriculeParent,
                'agent_id' => $parent['agent_id'],
                'retraite_id' => $parent['retraite_id'],
                'nom' => $nom ?: 'N/A',
                'prenoms' => $prenoms ?: 'N/A',
                'sexe' => $sexe === 'M' ? 'M' : 'F',
                'date_naissance' => $this->parseDate($dateNaissance) ?? now()->subYears(10),
                'prestation_familiale' => $prestationFamiliale,
                'scolarise' => $allocationScolaire > 0 ? 1 : 0,
                'actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            if (count($batch) >= $batchSize) {
                DB::table('enfants')->insertOrIgnore($batch);
                $inserted += count($batch);
                $batch = [];
            }
            
            $bar->advance();
        }
        
        // Insérer le reste
        if (!empty($batch)) {
            DB::table('enfants')->insertOrIgnore($batch);
            $inserted += count($batch);
        }
        
        fclose($handle);
        $bar->finish();
        
        $this->command->newLine();
        $this->command->info("✅ $inserted enfants importés depuis CSV !");
    }
    
    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
                $year = '20' . $matches[3];
                return Carbon::createFromFormat('Y-m-d', $year . '-' . $matches[2] . '-' . $matches[1]);
            }
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}