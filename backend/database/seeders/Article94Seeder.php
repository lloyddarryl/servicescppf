<?php
// Seeder pour les données de l'Article 94
// File: database/seeders/Article94Seeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParametrePension;
use Illuminate\Support\Facades\DB;

class Article94Seeder extends Seeder
{
    public function run()
    {
        $this->seedCoefficientsTemporels();
        $this->updateParametresPension();
    }

    /**
     * Créer les coefficients temporels selon l'Article 94
     */
    private function seedCoefficientsTemporels()
    {
        $coefficients = [
            // Ancien système (avant entrée en vigueur)
            ['annee' => 2015, 'coefficient' => 70.0, 'periode_debut' => 'août 2015', 'periode_fin' => 'juillet 2016'],
            ['annee' => 2016, 'coefficient' => 72.0, 'periode_debut' => 'août 2016', 'periode_fin' => 'juillet 2017'],
            ['annee' => 2017, 'coefficient' => 74.0, 'periode_debut' => 'août 2017', 'periode_fin' => 'juillet 2018'],
            ['annee' => 2018, 'coefficient' => 76.0, 'periode_debut' => 'août 2018', 'periode_fin' => 'juillet 2019'],
            ['annee' => 2019, 'coefficient' => 79.0, 'periode_debut' => 'août 2019', 'periode_fin' => 'juillet 2020'],
            ['annee' => 2020, 'coefficient' => 81.0, 'periode_debut' => 'août 2020', 'periode_fin' => 'juillet 2021'],
            ['annee' => 2021, 'coefficient' => 83.0, 'periode_debut' => 'août 2021', 'periode_fin' => 'juillet 2022'],
            ['annee' => 2022, 'coefficient' => 85.0, 'periode_debut' => 'août 2022', 'periode_fin' => 'juillet 2023'],
            ['annee' => 2023, 'coefficient' => 87.0, 'periode_debut' => 'août 2023', 'periode_fin' => 'juillet 2024'],
            
            // Nouveau système (après entrée en vigueur)
            ['annee' => 2024, 'coefficient' => 89.0, 'periode_debut' => 'août 2024', 'periode_fin' => 'juillet 2025'],
            ['annee' => 2025, 'coefficient' => 91.0, 'periode_debut' => 'août 2025', 'periode_fin' => 'juillet 2026'],
            ['annee' => 2026, 'coefficient' => 94.0, 'periode_debut' => 'août 2026', 'periode_fin' => 'juillet 2027'],
            ['annee' => 2027, 'coefficient' => 96.0, 'periode_debut' => 'août 2027', 'periode_fin' => 'juillet 2028'],
            ['annee' => 2028, 'coefficient' => 98.0, 'periode_debut' => 'août 2028', 'periode_fin' => 'juillet 2029'],
            ['annee' => 2029, 'coefficient' => 100.0, 'periode_debut' => 'août 2029', 'periode_fin' => 'indéterminée'],
        ];

        foreach ($coefficients as $coeff) {
            DB::table('coefficients_temporels')->updateOrInsert(
                ['annee' => $coeff['annee']],
                [
                    'coefficient' => $coeff['coefficient'],
                    'periode_debut' => $coeff['periode_debut'],
                    'periode_fin' => $coeff['periode_fin'],
                    'description' => "Coefficient applicable aux pensions liquidées de {$coeff['periode_debut']} à {$coeff['periode_fin']}",
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Mettre à jour les paramètres selon l'Article 94
     */
    private function updateParametresPension()
    {
        $parametresArticle94 = [
            [
                'code_parametre' => 'FORMULE_TAUX_LIQUIDATION',
                'libelle' => 'Formule de calcul du taux de liquidation',
                'valeur' => 1.8,
                'unite' => '% par année',
                'description' => 'Article 94: Taux = Nombre d\'années × 1,8%',
                'version_reglementation' => 'Article_94'
            ],
            [
                'code_parametre' => 'FORMULE_SOLDE_BASE',
                'libelle' => 'Formule de calcul du solde de base',
                'valeur' => 500,
                'unite' => 'FCFA par point',
                'description' => 'Article 94: Solde de base = Indice × 500',
                'version_reglementation' => 'Article_94'
            ],
            [
                'code_parametre' => 'DUREE_MIN_SERVICE_ART94',
                'libelle' => 'Durée minimale de service Article 94',
                'valeur' => 15,
                'unite' => 'années',
                'description' => 'Durée minimale pour bénéficier d\'une pension selon Article 94',
                'version_reglementation' => 'Article_94'
            ],
            [
                'code_parametre' => 'AGE_RETRAITE_ART94',
                'libelle' => 'Âge légal de retraite Article 94',
                'valeur' => 60,
                'unite' => 'années',
                'description' => 'Âge légal de départ à la retraite selon Article 94',
                'version_reglementation' => 'Article_94'
            ],
            [
                'code_parametre' => 'BONIF_SITUATION_FAMILIALE',
                'libelle' => 'Bonification situation familiale',
                'valeur' => 3.0,
                'unite' => '%',
                'description' => 'Bonification pour conjoint à charge',
                'version_reglementation' => 'Article_94'
            ],
            [
                'code_parametre' => 'BONIF_ENFANTS',
                'libelle' => 'Bonification pour enfants',
                'valeur' => 2.0,
                'unite' => '% par enfant',
                'description' => 'Bonification accordée par enfant à charge',
                'version_reglementation' => 'Article_94'
            ]
        ];

        foreach ($parametresArticle94 as $param) {
            ParametrePension::updateOrCreate(
                ['code_parametre' => $param['code_parametre']],
                [
                    'libelle' => $param['libelle'],
                    'valeur' => $param['valeur'],
                    'unite' => $param['unite'],
                    'description' => $param['description'],
                    'date_effet' => now()->startOfYear(),
                    'actif' => true,
                    'version_reglementation' => $param['version_reglementation']
                ]
            );
        }
    }
}