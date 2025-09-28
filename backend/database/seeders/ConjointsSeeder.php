<?php
// database/seeders/ConjointsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Conjoint;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;

class ConjointsSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/enfants_fur.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Le fichier enfants_fur.xlsx n'existe pas !");
            return;
        }

        $this->command->info("Extraction des conjoints depuis enfants_fur...");
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $conjointsUniques = [];
        $bar = $this->command->getOutput()->createProgressBar($highestRow - 1);
        
        // Parcourir pour extraire les conjoints (2ème parent - P2)
        for ($row = 2; $row <= $highestRow; $row++) {
            // Colonnes du 2ème parent (conjoint)
            $nMattirP2 = trim($worksheet->getCell('F' . $row)->getValue() ?? ''); // N_MATTIR_P2
            $cMattirP2 = trim($worksheet->getCell('G' . $row)->getValue() ?? ''); // C_MATTIR_P2
            $nomConjoint = trim($worksheet->getCell('AC' . $row)->getValue() ?? ''); // L_NM2TIR_P2
            $prenomsConjoint = trim($worksheet->getCell('AB' . $row)->getValue() ?? ''); // L_NMTIR_P2
            $dateNaissanceP2 = $worksheet->getCell('AD' . $row)->getValue(); // D_NAITIR_P2
            $sexeP2 = trim($worksheet->getCell('AE' . $row)->getValue() ?? ''); // C_SEXE_P2
            
            // Matricule du 1er parent (pour trouver l'agent/retraité)
            $nMattirP1 = trim($worksheet->getCell('H' . $row)->getValue() ?? '');
            $cMattirP1 = trim($worksheet->getCell('I' . $row)->getValue() ?? '');
            $matriculeP1 = $nMattirP1 . $cMattirP1;
            
            // Si pas de 2ème parent, continuer
            if (empty($prenomsConjoint) && empty($nomConjoint)) {
                $bar->advance();
                continue;
            }
            
            $matriculeP2 = $nMattirP2 . $cMattirP2;
            $key = $matriculeP1 . '_' . $matriculeP2;
            
            if (!isset($conjointsUniques[$key])) {
                $conjointsUniques[$key] = [
                    'matricule_parent' => $matriculeP1,
                    'matricule_conjoint' => $matriculeP2 ?: null,
                    'nom' => $nomConjoint ?: 'N/A',
                    'prenoms' => $prenomsConjoint ?: 'N/A',
                    'sexe' => $sexeP2 === 'M' ? 'M' : 'F',
                    'date_naissance' => $this->parseDate($dateNaissanceP2),
                ];
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->command->newLine();
        $this->command->info(count($conjointsUniques) . " conjoints uniques trouvés. Import...");
        
        // Insérer les conjoints
        $bar2 = $this->command->getOutput()->createProgressBar(count($conjointsUniques));
        $inserted = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($conjointsUniques as $conjointData) {
                // Trouver le parent
                $agent = Agent::where('matricule_solde', $conjointData['matricule_parent'])->first();
                $retraite = Retraite::where('numero_pension', $conjointData['matricule_parent'])->first();
                
                if (!$agent && !$retraite) {
                    $bar2->advance();
                    continue;
                }
                
                if (!$conjointData['date_naissance']) {
                    $bar2->advance();
                    continue; // Date de naissance obligatoire
                }
                
                // Vérifier si le conjoint existe déjà
                $existant = Conjoint::where(function($q) use ($agent, $retraite) {
                        if ($agent) $q->where('agent_id', $agent->id);
                        if ($retraite) $q->where('retraite_id', $retraite->id);
                    })
                    ->where('statut', 'ACTIF')
                    ->first();
                
                if (!$existant) {
                    Conjoint::create([
                        'agent_id' => $agent ? $agent->id : null,
                        'retraite_id' => $retraite ? $retraite->id : null,
                        'matricule_conjoint' => $conjointData['matricule_conjoint'],
                        'nom' => $conjointData['nom'],
                        'prenoms' => $conjointData['prenoms'],
                        'sexe' => $conjointData['sexe'],
                        'date_naissance' => $conjointData['date_naissance'],
                        'statut' => 'ACTIF',
                        'travaille' => !empty($conjointData['matricule_conjoint']),
                    ]);
                    $inserted++;
                }
                
                $bar2->advance();
            }
            
            DB::commit();
            $bar2->finish();
            $this->command->newLine();
            $this->command->info("✅ $inserted conjoints insérés !");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Erreur : " . $e->getMessage());
        }
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