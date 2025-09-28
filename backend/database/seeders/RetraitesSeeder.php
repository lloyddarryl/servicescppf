<?php
// database/seeders/RetraitesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Retraite;
use App\Models\HistoriquePaiement;
use Carbon\Carbon;

class RetraitesSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/retraite.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Le fichier retraite.xlsx n'existe pas !");
            return;
        }

        $data = Excel::toArray([], $filePath)[0];
        array_shift($data);
        
        $this->command->info("Import de " . count($data) . " retraités...");
        $bar = $this->command->getOutput()->createProgressBar(count($data));
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $row) {
                $numeroPension = trim($row[0] ?? '');
                $dateNaissance = $this->parseDate($row[3] ?? null);
                
                if (empty($numeroPension) || !$dateNaissance) {
                    $bar->advance();
                    continue; // Skip si pas de numéro pension ou date naissance invalide
                }
                
                $retraite = Retraite::where('numero_pension', $numeroPension)->first();
                
                if (!$retraite) {
                    $retraite = Retraite::create([
                        'numero_pension' => $numeroPension,
                        'nom' => trim($row[1] ?? '') ?: 'N/A',
                        'prenoms' => trim($row[2] ?? '') ?: 'N/A',
                        'date_naissance' => $dateNaissance,
                        'date_retraite' => $this->parseDate($row[4] ?? null) ?? now(),
                        'duree_service_mois' => (float)($row[5] ?? 0),
                        'taux_liquidation' => (float)($row[6] ?? 0),
                        'montant_pension' => (float)($row[7] ?? 0),
                        'indice_retraite' => (int)($row[8] ?? 0),
                        'corps' => trim($row[9] ?? '') ?: null,
                        'ancien_poste' => 'N/A',
                        'ancienne_direction' => 'N/A',
                        'status' => 'actif',
                    ]);
                }
                
                HistoriquePaiement::where('retraite_id', $retraite->id)
                    ->update(['numero_pension' => $numeroPension]);
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            $this->command->newLine();
            $this->command->info("✅ Import des retraités terminé !");
            
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
                $parsed = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
                if ($parsed->year < 1900) return null;
                return $parsed;
            }
            
            $parsed = Carbon::parse($date);
            if ($parsed->year < 1900) return null;
            return $parsed;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}