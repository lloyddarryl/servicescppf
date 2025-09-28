<?php
// database/seeders/ActifsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Agent;
use App\Models\Carriere;
use Carbon\Carbon;

class ActifsSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/imports/Actifs.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Le fichier Actifs.xlsx n'existe pas !");
            return;
        }

        $data = Excel::toArray([], $filePath)[0];
        array_shift($data);
        
        $this->command->info("Import de " . count($data) . " agents actifs...");
        $bar = $this->command->getOutput()->createProgressBar(count($data));
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $row) {
                $nomComplet = trim($row[0] ?? '');
                $parts = explode(' ', $nomComplet, 2);
                $nom = $parts[0] ?? '';
                $prenoms = $parts[1] ?? '';
                
                $matriculeSolde = trim($row[2] ?? '');
                $numAffiliation = trim($row[1] ?? '');
                
                if (empty($matriculeSolde)) continue;
                
                $agent = Agent::where('matricule_solde', $matriculeSolde)->first();
                
                if (!$agent) {
                    $dateNaissance = $this->parseDate($row[5] ?? null);
                    $datePriseService = $this->parseDate($row[6] ?? null);
                    
                    $agent = Agent::create([
                        'matricule_solde' => $matriculeSolde,
                        'num_affiliation' => $numAffiliation ?: null,
                        'nom' => $nom ?: 'N/A',
                        'prenoms' => $prenoms ?: 'N/A',
                        'date_naissance' => $dateNaissance,
                        'date_prise_service' => $datePriseService,
                        'sexe' => trim($row[4] ?? '') ?: null,
                        'situation_matrimoniale' => trim($row[3] ?? '') ?: null,
                        'duree_service_mois' => (int)($row[9] ?? 0),
                        'poste' => 'Agent',
                        'direction' => 'N/A',
                        'regime' => (int)($row[7] ?? 1),
                        'status' => 'actif',
                    ]);
                }
                
                Carriere::create([
                    'agent_id' => $agent->id,
                    'matricule_solde' => $matriculeSolde,
                    'date_debut' => $this->parseDate($row[6] ?? null),
                    'position' => 'ACTIVITE',
                    'etablissement' => 'CPPF',
                    'nombre_mois' => (int)($row[9] ?? 0),
                    'regime' => (int)($row[7] ?? 1),
                    'annuite' => (float)($row[10] ?? 0),
                    'statut' => 'VALIDE',
                    'observations' => 'Importé depuis Actifs.xlsx - ' . now()->format('d/m/Y H:i'),
                ]);
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            $this->command->newLine();
            $this->command->info("✅ Import des actifs terminé !");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Erreur : " . $e->getMessage());
            $this->command->error("Ligne: " . $e->getLine());
        }
    }
    
    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            // Si c'est un nombre Excel
            if (is_numeric($date) && $date > 0) {
                $parsed = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
                // Vérifier que la date est valide (après 1900)
                if ($parsed->year < 1900) return null;
                return $parsed;
            }
            
            // Si c'est déjà une date string
            $parsed = Carbon::parse($date);
            if ($parsed->year < 1900) return null;
            return $parsed;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}