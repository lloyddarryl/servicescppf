<?php
// database/migrations/2025_08_27_add_reminder_fields_to_rendez_vous_demandes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rendez_vous_demandes', function (Blueprint $table) {
            // Colonnes pour les rappels automatiques
            $table->boolean('rappel_j1_envoye')->default(false)->after('email_user_reponse_envoye');
            $table->timestamp('date_rappel_j1')->nullable()->after('rappel_j1_envoye');
            
            // Optionnel : autres rappels
            $table->boolean('rappel_j7_envoye')->default(false)->after('date_rappel_j1');
            $table->timestamp('date_rappel_j7')->nullable()->after('rappel_j7_envoye');
            
            // Colonne pour les notifications dashboard
            $table->boolean('notification_dashboard_lue')->default(false)->after('date_rappel_j7');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vous_demandes', function (Blueprint $table) {
            $table->dropColumn([
                'rappel_j1_envoye',
                'date_rappel_j1',
                'rappel_j7_envoye', 
                'date_rappel_j7',
                'notification_dashboard_lue'
            ]);
        });
    }
};