<?php
// Migration pour mettre à jour les tables selon l'Article 94
// File: database/migrations/2025_07_30_140000_update_pension_article94.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Ajouter des champs spécifiques à l'Article 94
        Schema::table('simulations_pension', function (Blueprint $table) {
            $table->decimal('coefficient_temporel', 5, 2)->nullable()->after('pension_totale');
            $table->decimal('pension_apres_coefficient', 12, 2)->nullable()->after('coefficient_temporel');
            $table->integer('annee_pension')->nullable()->after('pension_apres_coefficient');
            $table->string('methode_calcul')->default('Article_94')->after('annee_pension');
        });

        // Créer une table pour les coefficients temporels de l'Article 94
        Schema::create('coefficients_temporels', function (Blueprint $table) {
            $table->id();
            $table->integer('annee');
            $table->decimal('coefficient', 5, 2);
            $table->string('periode_debut'); // ex: "août 2024"
            $table->string('periode_fin');   // ex: "juillet 2025"
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique('annee');
        });

        // Mettre à jour les paramètres de pension
        Schema::table('parametres_pension', function (Blueprint $table) {
            $table->string('version_reglementation')->default('Article_94')->after('actif');
        });
    }

    public function down()
    {
        Schema::table('simulations_pension', function (Blueprint $table) {
            $table->dropColumn([
                'coefficient_temporel', 
                'pension_apres_coefficient', 
                'annee_pension', 
                'methode_calcul'
            ]);
        });

        Schema::dropIfExists('coefficients_temporels');

        Schema::table('parametres_pension', function (Blueprint $table) {
            $table->dropColumn('version_reglementation');
        });
    }
};