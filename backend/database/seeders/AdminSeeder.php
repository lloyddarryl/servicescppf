<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le Super Admin
        $superAdmin = Admin::create([
            'nom' => 'Super',
            'prenom' => 'Administrateur',
            'email' => 'superadmin@cppf.ga',
            'password' => Hash::make('SuperAdmin2024!'),
            'telephone' => '+241 77 65 16 01',
            'role' => 'super_admin',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        // Créer Admin 1
        Admin::create([
            'nom' => 'Admin',
            'prenom' => 'Premier',
            'email' => 'admin1@cppf.ga',
            'password' => Hash::make('Admin123!'),
            'telephone' => '+241 77 65 16 01',
            'role' => 'admin1',
            'statut' => 'actif',
            'email_verified_at' => now(),
            'created_by' => $superAdmin->id,
        ]);

        // Créer Admin 2
        Admin::create([
            'nom' => 'Admin',
            'prenom' => 'Second',
            'email' => 'admin2@cppf.ga',
            'password' => Hash::make('Admin123!'),
            'telephone' => '+241 01 00 00 02',
            'role' => 'admin2',
            'statut' => 'actif',
            'email_verified_at' => now(),
            'created_by' => $superAdmin->id,
        ]);

        echo "✅ Comptes administrateurs créés avec succès !\n";
        echo "📧 Super Admin: superadmin@cppf.ga / SuperAdmin2024!\n";
        echo "📧 Admin 1: admin1@cppf.ga / Admin123!\n";
        echo "📧 Admin 2: admin2@cppf.ga / Admin123!\n";
    }
}