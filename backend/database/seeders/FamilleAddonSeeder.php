<?php
// Fichier: database/seeders/FamilleAddonSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\Conjoint;
use App\Models\Enfant;

class FamilleAddonSeeder extends Seeder
{
    public function run()
    {
        echo "🏡 Ajout des familles aux données existantes...\n";

        // ✅ AJOUTER DES FAMILLES AUX AGENTS EXISTANTS
        $this->addFamillesToAgents();

        // ✅ AJOUTER DES FAMILLES AUX RETRAITÉS EXISTANTS
        $this->addFamillesToRetraites();

        echo "\n🎉 FAMILLES AJOUTÉES AVEC SUCCÈS !\n";
    }

    private function addFamillesToAgents()
    {
        echo "\n👨‍💼 AJOUT DE FAMILLES AUX AGENTS EXISTANTS\n";
        echo "===========================================\n";

        // Jean Claude MBEMBA (marié) - famille complète
        $agent = Agent::where('matricule_solde', '123456M')->first();
        if ($agent) {
            // Conjoint qui travaille
            Conjoint::updateOrCreate([
                'agent_id' => $agent->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'MBEMBA',
                'prenoms' => 'Sylvie Joséphine',
                'sexe' => 'F',
                'date_naissance' => '1978-08-20',
                'date_mariage' => '2005-06-15',
                'matricule_conjoint' => '998877F', // Travaille
                'profession' => 'Infirmière',
                'statut' => 'ACTIF'
            ]);

            // 2 enfants
            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2010123456'
            ], [
                'matricule_parent' => '123456',
                'nom' => 'MBEMBA',
                'prenoms' => 'Kevin Junior',
                'sexe' => 'M',
                'date_naissance' => '2010-03-10',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'CM2',
                'actif' => true
            ]);

            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2015567890'
            ], [
                'matricule_parent' => '123456',
                'nom' => 'MBEMBA',
                'prenoms' => 'Grace Ornella',
                'sexe' => 'F',
                'date_naissance' => '2015-11-25',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'CE2',
                'actif' => true
            ]);

            echo "✅ {$agent->prenoms} {$agent->nom} : conjoint + 2 enfants\n";
        }

        // Pierre François OBAME (marié) - conjoint au foyer
        $agent = Agent::where('matricule_solde', '345678B')->first();
        if ($agent) {
            // Conjoint qui ne travaille pas
            Conjoint::updateOrCreate([
                'agent_id' => $agent->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'OBAME',
                'prenoms' => 'Marie Christine',
                'sexe' => 'F',
                'date_naissance' => '1982-05-15',
                'date_mariage' => '2008-12-20',
                'nag_conjoint' => '1982051502', // NAG, ne travaille pas
                'profession' => 'Ménagère',
                'statut' => 'ACTIF'
            ]);

            // 3 enfants
            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2012345678'
            ], [
                'matricule_parent' => '345678',
                'nom' => 'OBAME',
                'prenoms' => 'Sandra Lisa',
                'sexe' => 'F',
                'date_naissance' => '2012-09-12',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => '6ème',
                'actif' => true
            ]);

            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2016789012'
            ], [
                'matricule_parent' => '345678',
                'nom' => 'OBAME',
                'prenoms' => 'David Michel',
                'sexe' => 'M',
                'date_naissance' => '2016-04-08',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'CE1',
                'actif' => true
            ]);

            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2020111222'
            ], [
                'matricule_parent' => '345678',
                'nom' => 'OBAME',
                'prenoms' => 'Ethan Prince',
                'sexe' => 'M',
                'date_naissance' => '2020-01-18',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'Petite section',
                'actif' => true
            ]);

            echo "✅ {$agent->prenoms} {$agent->nom} : conjoint + 3 enfants\n";
        }

        // Bernard Thierry MINTSA (marié) - famille avec ados
        $agent = Agent::where('matricule_solde', '567890D')->first();
        if ($agent) {
            // Conjoint qui travaille
            Conjoint::updateOrCreate([
                'agent_id' => $agent->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'MINTSA',
                'prenoms' => 'Patricia Solange',
                'sexe' => 'F',
                'date_naissance' => '1985-12-10',
                'date_mariage' => '2010-07-24',
                'matricule_conjoint' => '556677G', // Travaille
                'profession' => 'Comptable',
                'statut' => 'ACTIF'
            ]);

            // 1 enfant ado
            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2008654321'
            ], [
                'matricule_parent' => '567890',
                'nom' => 'MINTSA',
                'prenoms' => 'Jordan Alex',
                'sexe' => 'M',
                'date_naissance' => '2008-06-15',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'Première',
                'actif' => true
            ]);

            echo "✅ {$agent->prenoms} {$agent->nom} : conjoint + 1 enfant ado\n";
        }

        // Georgette Pascaline MOUNDOUNGA (mariée) - famille avec jeunes adultes
        $agent = Agent::where('matricule_solde', '678901E')->first();
        if ($agent) {
            // Conjoint qui travaille
            Conjoint::updateOrCreate([
                'agent_id' => $agent->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'MOUNDOUNGA',
                'prenoms' => 'Rodrigue Emmanuel',
                'sexe' => 'M',
                'date_naissance' => '1970-03-25',
                'date_mariage' => '1998-08-12',
                'matricule_conjoint' => '445566H', // Travaille
                'profession' => 'Avocat',
                'statut' => 'ACTIF'
            ]);

            // 2 enfants (1 majeur, 1 mineur)
            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2000987654'
            ], [
                'matricule_parent' => '678901',
                'nom' => 'MOUNDOUNGA',
                'prenoms' => 'Excellence Rodrigue',
                'sexe' => 'M',
                'date_naissance' => '2000-10-05',
                'prestation_familiale' => false, // Majeur
                'scolarise' => false,
                'niveau_scolaire' => 'Université terminée',
                'actif' => true,
                'observations' => 'Diplômé en Droit'
            ]);

            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2007456789'
            ], [
                'matricule_parent' => '678901',
                'nom' => 'MOUNDOUNGA',
                'prenoms' => 'Grâce Ornella',
                'sexe' => 'F',
                'date_naissance' => '2007-02-28',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'Terminale',
                'actif' => true
            ]);

            echo "✅ {$agent->prenoms} {$agent->nom} : conjoint + 2 enfants (1 majeur)\n";
        }

        // Laisser Marie Antoinette NZAMBA, Sylvie Marguerite ALLOGHO et Rodrigue Emmanuel ENGONE célibataires
        // Mais ajouter un enfant à Sylvie (mère célibataire)
        $agent = Agent::where('matricule_solde', '456789C')->first();
        if ($agent) {
            Enfant::updateOrCreate([
                'agent_id' => $agent->id,
                'enfant_id' => '2019333444'
            ], [
                'matricule_parent' => '456789',
                'nom' => 'ALLOGHO',
                'prenoms' => 'Destiny Chance',
                'sexe' => 'F',
                'date_naissance' => '2019-12-08',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'Petite section',
                'actif' => true
            ]);

            echo "✅ {$agent->prenoms} {$agent->nom} (célibataire) : 1 enfant\n";
        }
    }

    private function addFamillesToRetraites()
    {
        echo "\n👴 AJOUT DE FAMILLES AUX RETRAITÉS EXISTANTS\n";
        echo "===========================================\n";

        // Albert Jean MOUSSOUNDA - famille de retraité avec enfants adultes
        $retraite = Retraite::where('numero_pension', '2020001234')->first();
        if ($retraite) {
            // Conjoint retraitée
            Conjoint::updateOrCreate([
                'retraite_id' => $retraite->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'MOUSSOUNDA',
                'prenoms' => 'Bernadette Solange',
                'sexe' => 'F',
                'date_naissance' => '1958-02-18',
                'date_mariage' => '1980-05-20',
                'nag_conjoint' => '1958021801', // Retraitée, NAG
                'profession' => 'Ancienne institutrice',
                'statut' => 'ACTIF'
            ]);

            // 3 enfants adultes
            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '1985123789'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'MOUSSOUNDA',
                'prenoms' => 'Patricia Excellence',
                'sexe' => 'F',
                'date_naissance' => '1985-03-12',
                'prestation_familiale' => false,
                'scolarise' => false,
                'niveau_scolaire' => 'Master en Gestion',
                'actif' => true,
                'observations' => 'Directrice d\'entreprise'
            ]);

            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '1988456123'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'MOUSSOUNDA',
                'prenoms' => 'Jean Rodrigue',
                'sexe' => 'M',
                'date_naissance' => '1988-11-08',
                'prestation_familiale' => false,
                'scolarise' => false,
                'niveau_scolaire' => 'Ingénieur',
                'actif' => true,
                'observations' => 'Ingénieur informatique'
            ]);

            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '2005789456'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'MOUSSOUNDA',
                'prenoms' => 'Kevin Emmanuel',
                'sexe' => 'M',
                'date_naissance' => '2005-07-22',
                'prestation_familiale' => true, // Encore étudiant
                'scolarise' => true,
                'niveau_scolaire' => 'Terminale',
                'actif' => true
            ]);

            echo "✅ {$retraite->prenoms} {$retraite->nom} : conjoint + 3 enfants (1 mineur)\n";
        }

        // Henriette Solange NTOUTOUME - veuve avec enfants
        $retraite = Retraite::where('numero_pension', '2019005678')->first();
        if ($retraite && $retraite->sexe == 'F') {
            // Mise à jour situation matrimoniale pour être cohérent
            $retraite->update(['situation_matrimoniale' => 'Veuf(ve)']);

            // 2 enfants adultes
            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '1990234567'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'NTOUTOUME',
                'prenoms' => 'Michel Prince',
                'sexe' => 'M',
                'date_naissance' => '1990-05-15',
                'prestation_familiale' => false,
                'scolarise' => false,
                'niveau_scolaire' => 'Licence en Économie',
                'actif' => true,
                'observations' => 'Banquier'
            ]);

            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '1995678901'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'NTOUTOUME',
                'prenoms' => 'Grâce Divine',
                'sexe' => 'F',
                'date_naissance' => '1995-09-30',
                'prestation_familiale' => false,
                'scolarise' => false,
                'niveau_scolaire' => 'Master en Communication',
                'actif' => true,
                'observations' => 'Journaliste'
            ]);

            echo "✅ {$retraite->prenoms} {$retraite->nom} (veuve) : 2 enfants adultes\n";
        }

        // François Xavier KOUMBA - marié avec petits-enfants à charge
        $retraite = Retraite::where('numero_pension', '2021009876')->first();
        if ($retraite) {
            $retraite->update(['situation_matrimoniale' => 'Marié(e)']);

            // Conjoint retraitée
            Conjoint::updateOrCreate([
                'retraite_id' => $retraite->id,
                'statut' => 'ACTIF'
            ], [
                'nom' => 'KOUMBA',
                'prenoms' => 'Marie Antoinette',
                'sexe' => 'F',
                'date_naissance' => '1962-08-12',
                'date_mariage' => '1985-04-15',
                'nag_conjoint' => '1962081201',
                'profession' => 'Ancienne secrétaire',
                'statut' => 'ACTIF'
            ]);

            // Petit-fils à charge (situation particulière)
            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '2010998877'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'KOUMBA',
                'prenoms' => 'Darren Excellence',
                'sexe' => 'M',
                'date_naissance' => '2010-12-20',
                'prestation_familiale' => true,
                'scolarise' => true,
                'niveau_scolaire' => 'CM1',
                'actif' => true,
                'observations' => 'Petit-fils à charge'
            ]);

            echo "✅ {$retraite->prenoms} {$retraite->nom} : conjoint + 1 petit-fils à charge\n";
        }

        // Célestine Rose MOUNANGA - célibataire (divorcée)
        $retraite = Retraite::where('numero_pension', '2018012345')->first();
        if ($retraite) {
            $retraite->update(['situation_matrimoniale' => 'Divorcé(e)']);

            // 1 enfant adulte
            Enfant::updateOrCreate([
                'retraite_id' => $retraite->id,
                'enfant_id' => '1992555666'
            ], [
                'matricule_parent' => $retraite->numero_pension,
                'nom' => 'MOUNANGA',
                'prenoms' => 'Sandra Ornella',
                'sexe' => 'F',
                'date_naissance' => '1992-06-10',
                'prestation_familiale' => false,
                'scolarise' => false,
                'niveau_scolaire' => 'Infirmière diplômée',
                'actif' => true,
                'observations' => 'Infirmière à l\'hôpital'
            ]);

            echo "✅ {$retraite->prenoms} {$retraite->nom} (divorcée) : 1 fille adulte\n";
        }

        // Paul Martin NDONG et Catherine Micheline ELLA - rester célibataires/veufs sans enfants déclarés
        echo "✅ Paul Martin NDONG et Catherine Micheline ELLA : restent sans famille déclarée\n";
    }
}