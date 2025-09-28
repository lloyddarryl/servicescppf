<?php
// database/seeders/EnfantsFixedSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;

class EnfantsFixedSeeder extends Seeder
{
    private $agentsCache = [];
    private $retraitesCache = [];
    
    public function run(): void
    {
        $filePath = storage_path('app/imports/enfants_fur.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Fichier introuvable !");
            return;
        }

        $this->command->info("Chargement du cache des parents...");
        
        // Cache agents : matricule avec ET sans zéros
        foreach (Agent::all(['id', 'matricule_solde']) as $agent) {
            $mat = $agent->matricule_solde;
            $this->agentsCache[$mat] = $agent->id;
            
            // Version sans zéros (ex: 000037D -> 37D)
            $matSansZeros = ltrim($mat, '0');
            if ($matSansZeros !== $mat) {
                $this->agentsCache[$matSansZeros] = $agent->id;
            }
        }
        
        // Cache retraités
        foreach (Retraite::all(['id', 'numero_pension']) as $retraite) {
            $this->retraitesCache[$retraite->numero_pension] = $retraite->id;
        }
        
        $this->command->info("Cache : " . count($this->agentsCache) . " matricules agents");
        $this->command->info("Cache : " . count($this->retraitesCache) . " retraités");

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $this->command->info("Import de $highestRow lignes...");
        $bar = $this->command->getOutput()->createProgressBar($highestRow - 1);
        
        $batch = [];
        $inserted = 0;
        $batchSize = 500;
        
        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $enfantId = $worksheet->getCell("A$row")->getValue();
                $nMattirP1 = trim($worksheet->getCell("H$row")->getValue() ?? '');
                $cMattirP1 = trim($worksheet->getCell("I$row")->getValue() ?? '');
                $matriculeBrut = $nMattirP1 . $cMattirP1;
                
                if (empty($enfantId) || empty($matriculeBrut)) {
                    $bar->advance();
                    continue;
                }
                
                // Chercher avec matricule brut d'abord (sans zéros)
                $agentId = $this->agentsCache[$matriculeBrut] ?? null;
                $retraiteId = $this->retraitesCache[$matriculeBrut] ?? null;
                
                // Si pas trouvé, essayer avec zéros devant (format BDD agents)
                if (!$agentId && !$retraiteId) {
                    $matriculeAvecZeros = str_pad($nMattirP1, 6, '0', STR_PAD_LEFT) . $cMattirP1;
                    $agentId = $this->agentsCache[$matriculeAvecZeros] ?? null;
                    $retraiteId = $this->retraitesCache[$matriculeAvecZeros] ?? null;
                }
                
                if (!$agentId && !$retraiteId) {
                    $bar->advance();
                    continue;
                }
                
                $nom = trim($worksheet->getCell("Q$row")->getValue() ?? '') ?: 'N/A';
                $prenoms = trim($worksheet->getCell("P$row")->getValue() ?? '') ?: 'N/A';
                $sexe = trim($worksheet->getCell("S$row")->getValue() ?? '');
                $dateNaissance = $this->parseDate($worksheet->getCell("T$row")->getValue());
                $prestationFamiliale = (int)($worksheet->getCell("C$row")->getValue() ?? 0);
                $allocationScolaire = (int)($worksheet->getCell("E$row")->getValue() ?? 0);
                
                $batch[] = [
                    'enfant_id' => $enfantId,
                    'matricule_parent' => $matriculeBrut,
                    'agent_id' => $agentId,
                    'retraite_id' => $retraiteId,
                    'nom' => $nom,
                    'prenoms' => $prenoms,
                    'sexe' => $sexe === 'M' ? 'M' : 'F',
                    'date_naissance' => $dateNaissance ?? now()->subYears(10),
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
                    gc_collect_cycles();
                }
                
            } catch (\Exception $e) {
                // Continue
            }
            
            $bar->advance();
        }
        
        if (!empty($batch)) {
            DB::table('enfants')->insertOrIgnore($batch);
            $inserted += count($batch);
        }
        
        $spreadsheet->disconnectWorksheets();
        
        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ $inserted enfants importés !");
    }
    
    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            if (is_numeric($date) && $date > 0) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }
            
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