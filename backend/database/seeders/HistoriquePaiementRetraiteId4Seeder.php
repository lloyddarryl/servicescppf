<?php
// database/seeders/HistoriquePaiementRetraiteId4Seeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Retraite;
use App\Models\HistoriquePaiement;
use Carbon\Carbon;

class HistoriquePaiementRetraiteId4Seeder extends Seeder
{
    public function run()
    {
        // Récupérer le retraité avec ID 4
        $retraite = Retraite::find(4);
        
        if (!$retraite) {
            $this->command->error('Retraité avec ID 4 non trouvé');
            return;
        }

        $this->command->info("Génération des paiements pour {$retraite->prenoms} {$retraite->nom}");

        // Générer les paiements depuis août 2020 jusqu'à maintenant
        $dateDebut = Carbon::create(2020, 8, 1);
        $maintenant = Carbon::now();
        
        $datePaiement = $dateDebut->copy();
        
        while ($datePaiement->lte($maintenant)) {
            // Date de fin de mois pour le paiement
            $dateVersement = $datePaiement->copy()->endOfMonth();
            
            if ($dateVersement->isFuture()) {
                break;
            }
            
            // Générer numéro titre : année(2 chiffres) + mois + séquence aléatoire
            $annee = $dateVersement->format('y');
            $mois = $dateVersement->format('m');
            $sequence = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $numeroTitre = $annee . $mois . $sequence;
            
            // Vérifier que le numéro n'existe pas déjà
            while (HistoriquePaiement::where('numero_titre', $numeroTitre)->exists()) {
                $sequence = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $numeroTitre = $annee . $mois . $sequence;
            }
            
            // Mode de règlement aléatoire
            $modesReglement = [
                'P.NTOUM',
                'UGB LIBREVILLE',
                'AFG Bank LIBREVILLE',
                'BGFI Bank LIBREVILLE',
                'BICIG LIBREVILLE',
                'Ecobank LIBREVILLE'
            ];
            
            $modeReglement = $modesReglement[array_rand($modesReglement)];
            
            // Créer le paiement
            HistoriquePaiement::create([
                'retraite_id' => $retraite->id,
                'numero_titre' => $numeroTitre,
                'type_paiement' => 'D',
                'date_paiement' => $dateVersement,
                'nom_beneficiaire' => $retraite->nom,
                'prenoms_beneficiaire' => $retraite->prenoms,
                'complement_nom' => null,
                'regime' => 'MIL',
                'disponibilite' => 'Numérique',
                'mode_reglement' => $modeReglement,
                'montant_net' => $retraite->montant_pension,
                'montant_brut' => $retraite->montant_pension,
                'etat_paiement' => 'Actif',
                'created_at' => $dateVersement,
                'updated_at' => $dateVersement,
            ]);
            
            $this->command->line("✓ Paiement créé : {$numeroTitre} - {$dateVersement->format('d/m/Y')} - {$retraite->montant_pension} FCFA");
            
            $datePaiement->addMonth();
        }
        
        $this->command->info('✅ Génération terminée pour le retraité ID 4');
    }
}