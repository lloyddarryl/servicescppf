<?php
// File: database/seeders/PensionSimulatorSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\GrilleIndiciaire;
use App\Models\ParametrePension;
use App\Models\CarriereHistorique;
use Carbon\Carbon;

class PensionSimulatorSeeder extends Seeder
{
    public function run()
    {
        $this->seedParametresPension();
        $this->seedGrillesIndiciaires();
        $this->updateAgentsData();
        $this->seedCarriereHistorique();
    }

    /**
     * Créer les paramètres de pension
     */
    private function seedParametresPension()
    {
        $parametres = [
            [
                'code_parametre' => 'AGE_RETRAITE',
                'libelle' => 'Âge légal de retraite',
                'valeur' => 60,
                'unite' => 'années',
                'description' => 'Âge légal de départ à la retraite pour les fonctionnaires',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'DUREE_MIN_SERVICE',
                'libelle' => 'Durée minimale de service pour pension',
                'valeur' => 15,
                'unite' => 'années',
                'description' => 'Durée minimale de service pour bénéficier d\'une pension',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'TAUX_LIQUIDATION_BASE',
                'libelle' => 'Taux de liquidation de base',
                'valeur' => 37.5,
                'unite' => '%',
                'description' => 'Taux de base pour 15 années de service',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'TAUX_LIQUIDATION_MAX',
                'libelle' => 'Taux de liquidation maximum',
                'valeur' => 75,
                'unite' => '%',
                'description' => 'Taux maximum de liquidation (30 ans de service)',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'AUGMENTATION_ANNUELLE',
                'libelle' => 'Augmentation par année supplémentaire',
                'valeur' => 1.25,
                'unite' => '%',
                'description' => 'Augmentation du taux par année au-delà de 15 ans',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'VALEUR_POINT_INDICE',
                'libelle' => 'Valeur du point d\'indice',
                'valeur' => 500,
                'unite' => 'FCFA',
                'description' => 'Valeur monétaire d\'un point d\'indice',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'BONIF_CONJOINT',
                'libelle' => 'Bonification pour conjoint',
                'valeur' => 3,
                'unite' => '%',
                'description' => 'Bonification accordée pour conjoint à charge',
                'date_effet' => '2020-01-01',
                'actif' => true
            ],
            [
                'code_parametre' => 'BONIF_ENFANT',
                'libelle' => 'Bonification par enfant',
                'valeur' => 2,
                'unite' => '%',
                'description' => 'Bonification accordée par enfant à charge',
                'date_effet' => '2020-01-01',
                'actif' => true
            ]
        ];

        foreach ($parametres as $parametre) {
            ParametrePension::updateOrCreate(
                ['code_parametre' => $parametre['code_parametre']],
                $parametre
            );
        }
    }

    /**
     * Créer les grilles indiciaires
     */
    private function seedGrillesIndiciaires()
    {
        // Grille pour les civils (fonctionnaires)
        $grillesCivils = [
            // Catégorie A1
            ['CIVIL', 'A1', 1, 3, 1465, 2244, 500],
            ['CIVIL', 'A1', 2, 3, 1245, 2117, 500],
            ['CIVIL', 'A1', 3, 3, 1065, 1989, 500],
            ['CIVIL', 'A1', 4, 3, 945, 1862, 500],
            ['CIVIL', 'A1', 5, 3, 825, 1734, 500],
            
            // Catégorie A2
            ['CIVIL', 'A2', 1, 3, 860, 1527, 500],
            ['CIVIL', 'A2', 2, 3, 760, 1450, 500],
            ['CIVIL', 'A2', 3, 3, 660, 1374, 500],
            ['CIVIL', 'A2', 4, 3, 580, 1297, 500],
            ['CIVIL', 'A2', 5, 3, 500, 1220, 500],
            
            // Catégorie B1
            ['CIVIL', 'B1', 1, 3, 520, 995, 500],
            ['CIVIL', 'B1', 2, 3, 465, 951, 500],
            ['CIVIL', 'B1', 3, 3, 410, 906, 500],
            ['CIVIL', 'B1', 4, 3, 380, 862, 500],
            ['CIVIL', 'B1', 5, 3, 350, 817, 500],
            
            // Catégorie B2
            ['CIVIL', 'B2', 1, 3, 335, 699, 500],
            ['CIVIL', 'B2', 2, 3, 310, 673, 500],
            ['CIVIL', 'B2', 3, 3, 285, 648, 500],
            ['CIVIL', 'B2', 4, 3, 265, 622, 500],
            ['CIVIL', 'B2', 5, 3, 245, 597, 500],
            
            // Catégorie C
            ['CIVIL', 'C', 1, 2, 190, 537, 500],
            ['CIVIL', 'C', 2, 2, 180, 518, 500],
            ['CIVIL', 'C', 3, 2, 170, 499, 500],
            ['CIVIL', 'C', 4, 2, 160, 480, 500],
            ['CIVIL', 'C', 5, 2, 150, 461, 500]
        ];

        foreach ($grillesCivils as $grille) {
            GrilleIndiciaire::updateOrCreate(
                [
                    'type_grille' => $grille[0],
                    'categorie' => $grille[1],
                    'classe' => $grille[2]
                ],
                [
                    'duree_classe' => $grille[3],
                    'indice_ancien' => $grille[4],
                    'indice_nouveau' => $grille[5],
                    'valeur_point' => $grille[6]
                ]
            );
        }

        // Grille spéciale pour les indices élevés (directeurs, etc.)
        $grillesSpeciales = [
            ['CIVIL', 'SPECIAL', 1, null, 1001, 1001, 500],
            ['CIVIL', 'SPECIAL', 2, null, 1100, 1100, 500],
            ['CIVIL', 'SPECIAL', 3, null, 1200, 1200, 500],
            ['CIVIL', 'SPECIAL', 4, null, 1300, 1300, 500],
            ['CIVIL', 'SPECIAL', 5, null, 1400, 1400, 500]
        ];

        foreach ($grillesSpeciales as $grille) {
            GrilleIndiciaire::updateOrCreate(
                [
                    'type_grille' => $grille[0],
                    'categorie' => $grille[1],
                    'classe' => $grille[2]
                ],
                [
                    'duree_classe' => $grille[3],
                    'indice_ancien' => $grille[4],
                    'indice_nouveau' => $grille[5],
                    'valeur_point' => $grille[6]
                ]
            );
        }
    }

    /**
     * Mettre à jour les données des agents
     */
    private function updateAgentsData()
    {
        $agents = Agent::all();
        
        foreach ($agents as $agent) {
            // Attribuer un indice selon le poste
            $indice = $this->getIndiceByPoste($agent->poste);
            
            // Estimer une date de naissance (25 ans à l'embauche)
            $dateNaissance = Carbon::parse($agent->date_prise_service)->subYears(25);
            
            $agent->update([
                'indice' => $indice,
                'salaire_base' => $indice * 500,
                'corps' => 'FONCTIONNAIRES',
                'etablissement' => $agent->direction,
                'position_administrative' => 'ACTIVITE',
                'taux_cotisation' => 6.0,
                'montant_bonifications' => 0
            ]);
        }
    }

    /**
     * Créer l'historique de carrière pour les agents
     */
    private function seedCarriereHistorique()
    {
        $agents = Agent::all();
        
        foreach ($agents as $agent) {
            // Créer une entrée de carrière actuelle
            CarriereHistorique::create([
                'agent_id' => $agent->id,
                'matricule_assure' => $agent->matricule_solde,
                'grade_code' => 211, // Code générique
                'grade_libelle' => $agent->grade,
                'indice' => $agent->indice,
                'statut' => 'FONCTIONNAIRES',
                'position_administrative' => 'ACTIVITE',
                'presence' => 'PRESENT',
                'fonction' => $agent->poste,
                'corps' => 'FONCTIONNAIRES',
                'etablissement' => $agent->direction,
                'departement_ministere' => $this->getDepartementByDirection($agent->direction),
                'salaire_brut' => $agent->salaire_base,
                'salaire_net' => $agent->salaire_base * 0.85, // Estimation
                'salaire_base' => $agent->salaire_base,
                'montant_bonifications' => 0,
                'cotisations' => $agent->salaire_base * 0.06,
                'detachement' => false,
                'date_carriere' => $agent->date_prise_service ?? now(),
                'taux_cotisation' => 6.0,
                'valide' => true
            ]);

            // Créer quelques entrées historiques simulées
            $this->createHistoriqueEntries($agent);
        }
    }

    /**
     * Obtenir l'indice selon le poste
     */
    private function getIndiceByPoste($poste)
    {
        $indices = [
            'Directeur' => 1200,
            'Chef de Service' => 1001,
            'Attaché' => 800,
            'Secrétaire' => 600,
            'Comptable' => 900,
            'Conseiller' => 1100
        ];

        foreach ($indices as $motCle => $indice) {
            if (stripos($poste, $motCle) !== false) {
                return $indice;
            }
        }

        return 800; // Indice par défaut
    }

    /**
     * Obtenir le département selon la direction
     */
    private function getDepartementByDirection($direction)
    {
        $departements = [
            'Direction Générale' => 'PRESIDENCE',
            'Direction des Prestations' => 'AFFAIRES SOCIALES',
            'Direction Financière' => 'FINANCES',
            'Direction Juridique' => 'JUSTICE',
            'Direction Informatique' => 'MODERNISATION',
            'Direction Communication' => 'COMMUNICATION'
        ];

        foreach ($departements as $dir => $dept) {
            if (stripos($direction, $dir) !== false) {
                return $dept;
            }
        }

        return 'ADMINISTRATION GENERALE';
    }

    /**
     * Créer des entrées historiques pour un agent
     */
    private function createHistoriqueEntries($agent)
    {
        $dateDebut = Carbon::parse($agent->date_prise_service);
        $maintenant = now();
        
        // Créer une progression de carrière simulée
        $indiceInitial = max(400, $agent->indice - 400);
        $progressions = [
            ['date' => $dateDebut, 'indice' => $indiceInitial, 'grade' => 'Attaché'],
            ['date' => $dateDebut->copy()->addYears(5), 'indice' => $indiceInitial + 200, 'grade' => 'Attaché Principal'],
            ['date' => $dateDebut->copy()->addYears(10), 'indice' => $agent->indice, 'grade' => $agent->grade]
        ];

        foreach ($progressions as $i => $progression) {
            if ($progression['date']->isBefore($maintenant) && $i > 0) {
                CarriereHistorique::create([
                    'agent_id' => $agent->id,
                    'matricule_assure' => $agent->matricule_solde,
                    'grade_code' => 200 + $i,
                    'grade_libelle' => $progression['grade'],
                    'indice' => $progression['indice'],
                    'statut' => 'FONCTIONNAIRES',
                    'position_administrative' => 'ACTIVITE',
                    'presence' => 'PRESENT',
                    'fonction' => $agent->poste,
                    'corps' => 'FONCTIONNAIRES',
                    'etablissement' => $agent->direction,
                    'departement_ministere' => $this->getDepartementByDirection($agent->direction),
                    'salaire_brut' => $progression['indice'] * 500,
                    'salaire_net' => $progression['indice'] * 500 * 0.85,
                    'salaire_base' => $progression['indice'] * 500,
                    'montant_bonifications' => 0,
                    'cotisations' => $progression['indice'] * 500 * 0.06,
                    'detachement' => false,
                    'date_carriere' => $progression['date'],
                    'taux_cotisation' => 6.0,
                    'valide' => true
                ]);
            }
        }
    }
}