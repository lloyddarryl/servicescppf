<?php
// Fichier: database/seeders/DatabaseSeeder.php - Version complète

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "🌱 DÉMARRAGE DU SEEDING COMPLET...\n";
        echo "=================================\n\n";

        $this->call([
            // ✅ 1. D'abord créer les agents et retraités (tes seeders existants)
            AgentSeeder::class,
            RetraiteSeeder::class,
            
            // ✅ 2. Ensuite ajouter les familles aux données existantes
            FamilleAddonSeeder::class,
        ]);

        echo "\n🎉 SEEDING COMPLET TERMINÉ !\n";
        echo "============================\n";
        echo "📋 Utilisez 'php debug_famille.php' pour voir les résultats\n";
        echo "🔑 Tous les comptes ont first_login=true (première connexion)\n";
        echo "💡 Mot de passe temporaire = chiffres du matricule/n° pension\n";

        // Nouveau seeder pour l'historique des paiements
    $this->call([
        HistoriquePaiementSeeder::class,
    ]);
    
    $this->command->info('✅ Historique des paiements généré avec succès');

    }
}