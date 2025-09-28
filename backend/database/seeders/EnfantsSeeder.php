<?php
// database/seeders/EnfantsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Enfant;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;

class EnfantsSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);
        
        $filePath = storage_path('app/imports/enfants_fur.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Le fichier enfants_fur.xlsx n'existe pas !");
            return;
        }

        $this->command->info("Lecture du fichier Excel...");
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $this->command->info("Import de " . ($highestRow - 1) . " enfants...");
        $bar = $this->command->getOutput()->createProgressBar($highestRow - 1);
        
        $chunkSize = 500;
        $inserted = 0;
        
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $endRow = min($startRow + $chunkSize - 1, $highestRow);
            
            DB::beginTransaction();
            
            try {
                for ($row = $startRow; $row <= $endRow; $row++) {
                    $enfantId = $worksheet->getCell('A' . $row)->getValue();
                    $nMattirP1 = $worksheet->getCell('H' . $row)->getValue();
                    $cMattirP1 = $worksheet->getCell('I' . $row)->getValue();
                    $matriculeParent = trim($nMattirP1) . trim($cMattirP1);
                    
                    $nom = trim($worksheet->getCell('Q' . $row)->getValue() ?? '');
                    $prenoms = trim($worksheet->getCell('P' . $row)->getValue() ?? '');
                    $sexe = trim($worksheet->getCell('S' . $row)->getValue() ?? '');
                    $dateNaissanceRaw = $worksheet->getCell('T' . $row)->getValue();
                    $prestationFamiliale = (int)($worksheet->getCell('C' . $row)->getValue() ?? 0);
                    $allocationScolaire = (int)($worksheet->getCell('E' . $row)->getValue() ?? 0);
                    
                    if (empty($enfantId) || empty($matriculeParent)) {
                        $bar->advance();
                        continue;
                    }
                    
                    $agent = Agent::where('matricule_solde', $matriculeParent)->first();
                    $retraite = Retraite::where('numero_pension', $matriculeParent)->first();
                    
                    if (!$agent && !$retraite) {
                        $bar->advance();
                        continue;
                    }
                    
                    $dateNaissance = $this->parseDate($dateNaissanceRaw);
                    
                    $existingEnfant = Enfant::where('enfant_id', $enfantId)
                        ->where(function($q) use ($agent, $retraite) {
                            if ($agent) $q->where('agent_id', $agent->id);
                            if ($retraite) $q->where('retraite_id', $retraite->id);
                        })
                        ->first();
                    
                    if (!$existingEnfant) {
                        Enfant::create([
                            'enfant_id' => $enfantId,
                            'matricule_parent' => $matriculeParent,
                            'agent_id' => $agent ? $agent->id : null,
                            'retraite_id' => $retraite ? $retraite->id : null,
                            'nom' => $nom ?: 'N/A',
                            'prenoms' => $prenoms ?: 'N/A',
                            'sexe' => $sexe === 'M' ? 'M' : 'F',
                            'date_naissance' => $dateNaissance ?? now()->subYears(10),
                            'prestation_familiale' => $prestationFamiliale,
                            'scolarise' => $allocationScolaire > 0 ? 1 : 0,
                            'actif' => 1,
                        ]);
                        $inserted++;
                    }
                    
                    $bar->advance();
                }
                
                DB::commit();
                
                // Libérer la mémoire
                gc_collect_cycles();
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("\n❌ Erreur chunk $startRow-$endRow : " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ Import terminé ! $inserted enfants insérés.");
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
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