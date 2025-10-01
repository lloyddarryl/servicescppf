<?php
// database/seeders/CarrieresSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Agent;
use App\Models\Carriere;
use Carbon\Carbon;

class CarrieresSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/DarLot6.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Le fichier n'existe pas !");
            return;
        }

        $data = Excel::toArray([], $filePath)[0];
        array_shift($data); // Enlever les en-têtes
        
        $this->command->info("Import de " . count($data) . " carrières...");
        
        // Cache agents
        $agentsCache = [];
        foreach (Agent::all(['id', 'matricule_solde']) as $agent) {
            $agentsCache[$agent->matricule_solde] = $agent->id;
            $matSansZeros = ltrim($agent->matricule_solde, '0');
            if ($matSansZeros !== $agent->matricule_solde) {
                $agentsCache[$matSansZeros] = $agent->id;
            }
        }
        
        $bar = $this->command->getOutput()->createProgressBar(count($data));
        
        DB::beginTransaction();
        
        try {
            $inserted = 0;
            $notFound = 0;
            $numeroOrdreByAgent = [];
            
            foreach ($data as $row) {
                $matricule = trim($row[0] ?? '');
                
                if (empty($matricule)) {
                    $bar->advance();
                    continue;
                }
                
                $agentId = $agentsCache[$matricule] ?? null;
                
                if (!$agentId) {
                    $notFound++;
                    $bar->advance();
                    continue;
                }
                
                $dateDebut = $this->parseDate($row[1] ?? null);
                if (!$dateDebut) {
                    $bar->advance();
                    continue;
                }
                
                if (!isset($numeroOrdreByAgent[$agentId])) {
                    $numeroOrdreByAgent[$agentId] = 1;
                } else {
                    $numeroOrdreByAgent[$agentId]++;
                }
                
                Carriere::create([
                    'agent_id' => $agentId,
                    'matricule_solde' => $matricule,
                    'numero_ordre' => $numeroOrdreByAgent[$agentId],
                    'date_debut' => $dateDebut,
                    'date_fin' => $this->parseDate($row[2] ?? null),
                    'position' => trim($row[3] ?? 'ACTIVITE'),
                    'etablissement' => trim($row[4] ?? '') ?: null,
                    'corps' => trim($row[5] ?? '') ?: null,
                    'grade' => (int)($row[6] ?? 0),
                    'indice' => (int)($row[7] ?? 0),
                    'retenue' => (float)($row[8] ?? 0),
                    'nombre_mois' => (int)($row[9] ?? 0),
                    'regime' => (int)($row[10] ?? 1),
                    'sous_regime' => trim($row[11] ?? 'CIV'),
                    'annuite' => (float)($row[12] ?? 0),
                    'total_cotisations' => (float)($row[8] ?? 0) * (int)($row[9] ?? 0),
                    'statut' => 'VALIDE',
                    'observations' => 'Importé DarLot1.xlsx - ' . now()->format('d/m/Y H:i'),
                ]);
                
                $inserted++;
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            $this->command->newLine();
            $this->command->info("✅ $inserted carrières importées avec TOUTES les données !");
            
            if ($notFound > 0) {
                $this->command->warn("⚠️  $notFound ignorées (agents non trouvés)");
            }
            
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
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}