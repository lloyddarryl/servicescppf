<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use Carbon\Carbon;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agents = [
            [
                'matricule_solde' => '123456M',
                'nom' => 'MBEMBA',
                'prenoms' => 'Jean Claude',
                'poste' => 'Directeur des Ressources Humaines',
                'direction' => 'Direction Générale',
                'grade' => 'Administrateur Principal',
                'date_prise_service' => '2010-03-15',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'matricule_solde' => '234567A',
                'nom' => 'NZAMBA',
                'prenoms' => 'Marie Antoinette',
                'poste' => 'Chef de Service Prestations',
                'direction' => 'Direction des Prestations Familiales',
                'grade' => 'Attaché Principal',
                'date_prise_service' => '2015-07-20',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'matricule_solde' => '345678B',
                'nom' => 'OBAME',
                'prenoms' => 'Pierre François',
                'poste' => 'Comptable Principal',
                'direction' => 'Direction Financière et Comptable',
                'grade' => 'Contrôleur des Services Financiers',
                'date_prise_service' => '2012-01-10',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'matricule_solde' => '456789C',
                'nom' => 'ALLOGHO',
                'prenoms' => 'Sylvie Marguerite',
                'poste' => 'Secrétaire de Direction',
                'direction' => 'Direction Générale',
                'grade' => 'Secrétaire Principal',
                'date_prise_service' => '2018-09-05',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'matricule_solde' => '567890D',
                'nom' => 'MINTSA',
                'prenoms' => 'Bernard Thierry',
                'poste' => 'Chef de Service Informatique',
                'direction' => 'Direction des Systèmes d\'Information',
                'grade' => 'Ingénieur Principal',
                'date_prise_service' => '2014-11-12',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            // Format 13 caractères
            [
                'matricule_solde' => '89012E',
                'nom' => 'MOUNDOUNGA',
                'prenoms' => 'Georgette Pascaline',
                'poste' => 'Directrice des Affaires Juridiques',
                'direction' => 'Direction des Affaires Juridiques',
                'grade' => 'Conseiller Juridique Principal',
                'date_prise_service' => '2008-05-30',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'matricule_solde' => '890123F',
                'nom' => 'ENGONE',
                'prenoms' => 'Rodrigue Emmanuel',
                'poste' => 'Chef de Service Communication',
                'direction' => 'Direction de la Communication',
                'grade' => 'Attaché de Communication',
                'date_prise_service' => '2016-02-18',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ]
        ];

        foreach ($agents as $agentData) {
            Agent::create($agentData);
        }
    }
}