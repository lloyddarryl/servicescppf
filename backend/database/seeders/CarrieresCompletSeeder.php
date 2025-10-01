<?php
// database/seeders/CarrieresCompletSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Agent;
use App\Models\Carriere;
use Carbon\Carbon;

class CarrieresCompletSeeder extends Seeder
{
    public function run(): void
    {
        // Cache agents une seule fois
        $this->command->info("Chargement du cache des agents...");
        $agentsCache = $this->buildAgentsCache();
        $this->command->info("Cache : " . count($agentsCache) . " matricules");
        
        $numeroOrdreByAgent = [];
        
        // Charger les 6 fichiers
        for ($i = 1; $i <= 6; $i++) {
            $fileName = "DarLot$i.xlsx";
            $filePath = storage_path("app/imports/$fileName");
            
            if (!file_exists($filePath)) {
                $this->command->warn("⚠️  $fileName introuvable, ignoré.");
                continue;
            }
            
            $this->command->info("\n📂 Import de $fileName...");
            
            $result = $this->importFile(
                $filePath, 
                $agentsCache, 
                $numeroOrdreByAgent
            );
            
            $this->command->info("✅ $fileName : {$result['inserted']} carrières importées");
            
            if ($result['notFound'] > 0) {
                $this->command->warn("   ⚠️  {$result['notFound']} ignorées (agents non trouvés)");
            }
        }
        
        $this->command->newLine();
        $this->command->info("🎉 Import terminé pour tous les fichiers !");
    }
    
    private function buildAgentsCache(): array
    {
        $cache = [];
        
        foreach (Agent::all(['id', 'matricule_solde']) as $agent) {
            $cache[$agent->matricule_solde] = $agent->id;
            
            // Version sans zéros
            $matSansZeros = ltrim($agent->matricule_solde, '0');
            if ($matSansZeros !== $agent->matricule_solde) {
                $cache[$matSansZeros] = $agent->id;
            }
        }
        
        return $cache;
    }
    
    private function importFile(string $filePath, array $agentsCache, array &$numeroOrdreByAgent): array
    {
        $data = Excel::toArray([], $filePath)[0];
        array_shift($data); // Enlever en-têtes
        
        $bar = $this->command->getOutput()->createProgressBar(count($data));
        
        $inserted = 0;
        $notFound = 0;
        
        DB::beginTransaction();
        
        try {
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
                
                // Incrémenter numero_ordre pour cet agent
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
                    'observations' => 'Importé depuis ' . basename($filePath) . ' - ' . now()->format('d/m/Y H:i'),
                ]);
                
                $inserted++;
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            $this->command->newLine();
            
            return ['inserted' => $inserted, 'notFound' => $notFound];
            
        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->command->newLine();
            $this->command->error("Erreur : " . $e->getMessage());
            
            return ['inserted' => 0, 'notFound' => 0];
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