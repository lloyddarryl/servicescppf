<?php
// database/seeders/HistoriquePaiementSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Retraite;
use App\Models\HistoriquePaiement;
use Carbon\Carbon;

class HistoriquePaiementSeeder extends Seeder
{
    public function run()
    {
        $retraites = Retraite::all();

        foreach ($retraites as $retraite) {
            $this->command->info("Génération des paiements pour {$retraite->prenoms} {$retraite->nom}");
            
            // Commencer le mois suivant la retraite
            $dateDebut = Carbon::parse($retraite->date_retraite)->addMonth()->startOfMonth();
            $dateFin = Carbon::now();
            
            $currentDate = $dateDebut->copy();
            
            while ($currentDate->lte($dateFin)) {
                // Date de paiement = fin du mois
                $datePaiement = $currentDate->copy()->endOfMonth();
                
                // Ne pas créer de paiement futur
                if ($datePaiement->isFuture()) {
                    break;
                }
                
                // Numéro titre : 8 chiffres uniquement
                $numeroTitre = $this->genererNumero();
                
                // Montants
                $montantNet = $retraite->montant_pension;
                $retenues = round($montantNet * 0.015);
                $montantBrut = $montantNet + $retenues;
                
                // Créer le paiement
                HistoriquePaiement::create([
                    'retraite_id' => $retraite->id,
                    'numero_titre' => $numeroTitre,
                    'type_paiement' => 'D',
                    'date_paiement' => $datePaiement,
                    'nom_beneficiaire' => $retraite->nom,
                    'prenoms_beneficiaire' => $retraite->prenoms,
                    'regime' => 'MAS',
                    'disponibilite' => 'Bancaire',
                    'mode_reglement' => 'AFG Bank LIBREVILLE',
                    'montant_net' => $montantNet,
                    'montant_brut' => $montantBrut,
                    'rappels' => 0,
                    'retenues' => $retenues,
                    'etat_paiement' => 'Versé'
                ]);
                
                $this->command->line("Paiement créé : {$numeroTitre} - {$datePaiement->format('d/m/Y')}");
                
                $currentDate->addMonth();
            }
        }
    }
    
    private function genererNumero()
    {
        do {
            $numero = rand(10000000, 99999999); // 8 chiffres
        } while (HistoriquePaiement::where('numero_titre', $numero)->exists());
        
        return (string) $numero;
    }
}