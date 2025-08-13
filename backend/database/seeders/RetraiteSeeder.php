<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Retraite;
use Carbon\Carbon;

class RetraiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $retraites = [
            [
                'numero_pension' => '2020001234',
                'nom' => 'MOUSSOUNDA',
                'prenoms' => 'Albert Jean',
                'date_naissance' => '1955-08-15',
                'date_retraite' => '2020-08-31',
                'ancien_poste' => 'Directeur Général',
                'ancienne_direction' => 'Direction Générale',
                'parcours_professionnel' => 'Directeur Général (2010-2020), Directeur Adjoint (2005-2010), Chef de Service (2000-2005)',
                'montant_pension' => 850000,
                'sexe' => 'M',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'numero_pension' => '2019005678',
                'nom' => 'NTOUTOUME',
                'prenoms' => 'Henriette Solange',
                'date_naissance' => '1959-12-03',
                'date_retraite' => '2019-12-31',
                'ancien_poste' => 'Directrice des Prestations Familiales',
                'ancienne_direction' => 'Direction des Prestations Familiales',
                'parcours_professionnel' => 'Directrice (2015-2019), Chef de Service (2010-2015), Attachée (2005-2010)',
                'montant_pension' => 720000,
                'sexe' => 'F',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'numero_pension' => '2021009876',
                'nom' => 'KOUMBA',
                'prenoms' => 'François Xavier',
                'date_naissance' => '1956-04-22',
                'date_retraite' => '2021-04-30',
                'ancien_poste' => 'Directeur Financier et Comptable',
                'ancienne_direction' => 'Direction Financière et Comptable',
                'parcours_professionnel' => 'Directeur Financier (2012-2021), Chef Comptable (2008-2012), Contrôleur (2003-2008)',
                'montant_pension' => 780000,
                'sexe' => 'M',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'numero_pension' => '2018012345',
                'nom' => 'MOUNANGA',
                'prenoms' => 'Célestine Rose',
                'date_naissance' => '1958-11-10',
                'date_retraite' => '2018-11-30',
                'ancien_poste' => 'Secrétaire Générale',
                'ancienne_direction' => 'Secrétariat Général',
                'parcours_professionnel' => 'Secrétaire Générale (2010-2018), Chef de Cabinet (2005-2010), Secrétaire de Direction (2000-2005)',
                'montant_pension' => 650000,
                'sexe' => 'F',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'numero_pension' => '2022003456',
                'nom' => 'NDONG',
                'prenoms' => 'Paul Martin',
                'date_naissance' => '1957-06-18',
                'date_retraite' => '2022-06-30',
                'ancien_poste' => 'Chef de Service Juridique',
                'ancienne_direction' => 'Direction des Affaires Juridiques',
                'parcours_professionnel' => 'Chef de Service Juridique (2015-2022), Conseiller Juridique (2010-2015), Attaché Juridique (2005-2010)',
                'montant_pension' => 580000,
                'sexe' => 'M',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ],
            [
                'numero_pension' => '2020007890',
                'nom' => 'ELLA',
                'prenoms' => 'Catherine Micheline',
                'date_naissance' => '1960-02-14',
                'date_retraite' => '2020-02-29',
                'ancien_poste' => 'Chef de Service Ressources Humaines',
                'ancienne_direction' => 'Direction des Ressources Humaines',
                'parcours_professionnel' => 'Chef de Service RH (2012-2020), Gestionnaire RH (2008-2012), Assistante RH (2003-2008)',
                'montant_pension' => 520000,
                'sexe' => 'F',
                'status' => 'actif',
                'is_active' => true,
                'first_login' => true,
                'password_changed' => false,
            ]
        ];

        foreach ($retraites as $retraiteData) {
            // ✅ Utiliser updateOrCreate pour éviter les doublons
            Retraite::updateOrCreate(
                ['numero_pension' => $retraiteData['numero_pension']], // Critère de recherche
                $retraiteData // Données à créer/mettre à jour
            );
        }

        $this->command->info('✅ ' . count($retraites) . ' retraités créés/mis à jour avec succès');
    }
}