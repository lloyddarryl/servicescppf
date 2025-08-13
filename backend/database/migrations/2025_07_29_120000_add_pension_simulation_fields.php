<?php
// Migration pour adapter la base de données pour le simulateur de pension
// File: database/migrations/2025_07_29_120000_add_pension_simulation_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Ajouter les champs manquants à la table agents
        Schema::table('agents', function (Blueprint $table) {
            $table->integer('indice')->nullable()->after('grade');
            $table->string('corps')->nullable()->after('indice');
            $table->string('etablissement')->nullable()->after('direction');
            $table->decimal('salaire_base', 12, 2)->nullable()->after('indice');
            $table->decimal('montant_bonifications', 12, 2)->default(0)->after('salaire_base');
            $table->string('position_administrative')->default('ACTIVITE')->after('status');
            $table->decimal('taux_cotisation', 5, 2)->default(6.00)->after('montant_bonifications');
        });

        // Ajouter les champs manquants à la table retraites
        Schema::table('retraites', function (Blueprint $table) {
            $table->integer('indice_retraite')->nullable()->after('montant_pension');
            $table->string('corps')->nullable()->after('indice_retraite');
            $table->decimal('duree_service_mois', 8, 2)->nullable()->after('date_retraite');
            $table->decimal('taux_liquidation', 5, 2)->nullable()->after('duree_service_mois');
            $table->decimal('salaire_reference', 12, 2)->nullable()->after('taux_liquidation');
            $table->decimal('pension_base', 12, 2)->nullable()->after('salaire_reference');
            $table->decimal('bonifications_totales', 12, 2)->default(0)->after('pension_base');
        });

        // Créer une table pour l'historique des carrières
        Schema::create('carrieres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('matricule_assure'); // mat_ass du CSV
            $table->integer('grade_code'); // code numérique du grade
            $table->string('grade_libelle')->nullable();
            $table->integer('indice');
            $table->string('statut'); // FONCTIONNAIRES, etc.
            $table->string('position_administrative'); // ACTIVITE, etc.
            $table->string('presence')->default('PRESENT'); // PRESENT, ABSENT
            $table->string('fonction')->nullable();
            $table->string('corps')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('departement_ministere')->nullable();
            $table->decimal('salaire_brut', 12, 2)->nullable();
            $table->decimal('salaire_net', 12, 2)->nullable();
            $table->decimal('salaire_base', 12, 2)->nullable();
            $table->decimal('montant_bonifications', 12, 2)->default(0);
            $table->decimal('cotisations', 12, 2)->nullable();
            $table->boolean('detachement')->default(false);
            $table->date('date_debut_detachement')->nullable();
            $table->date('date_fin_detachement')->nullable();
            $table->date('date_suspension_solde')->nullable();
            $table->date('date_carriere'); // date_car du CSV
            $table->string('etat_general')->nullable();
            $table->decimal('taux_cotisation', 5, 2)->default(6.00);
            $table->boolean('valide')->default(true);
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->index(['agent_id', 'date_carriere']);
            $table->index('matricule_assure');
        });

        // Créer une table pour les grilles indiciaires
        Schema::create('grilles_indiciaires', function (Blueprint $table) {
            $table->id();
            $table->string('type_grille'); // CIVIL, MILITAIRE, etc.
            $table->string('categorie'); // A1, A2, B1, B2, C1, C2, etc.
            $table->integer('classe')->nullable();
            $table->integer('duree_classe')->nullable(); // en années
            $table->integer('indice_ancien')->nullable(); // ancien système
            $table->integer('indice_nouveau')->nullable(); // nouveau système
            $table->decimal('valeur_point', 8, 2)->default(500.00); // pour calcul salaire
            $table->timestamps();

            $table->unique(['type_grille', 'categorie', 'classe']);
            $table->index('indice_nouveau');
        });

        // Créer une table pour les paramètres de calcul de pension
        Schema::create('parametres_pension', function (Blueprint $table) {
            $table->id();
            $table->string('code_parametre')->unique();
            $table->string('libelle');
            $table->decimal('valeur', 10, 4);
            $table->string('unite')->nullable(); // %, années, etc.
            $table->text('description')->nullable();
            $table->date('date_effet');
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Créer une table pour les simulations de pension
        Schema::create('simulations_pension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->date('date_simulation');
            $table->date('date_retraite_prevue');
            $table->decimal('duree_service_simulee', 8, 2); // en années
            $table->integer('indice_simule');
            $table->decimal('salaire_reference', 12, 2);
            $table->decimal('taux_liquidation', 5, 2);
            $table->decimal('pension_base', 12, 2);
            $table->decimal('bonifications', 12, 2)->default(0);
            $table->decimal('pension_totale', 12, 2);
            $table->json('parametres_utilises')->nullable(); // sauvegarde des paramètres
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->index(['agent_id', 'date_simulation']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulations_pension');
        Schema::dropIfExists('parametres_pension');
        Schema::dropIfExists('grilles_indiciaires');
        Schema::dropIfExists('carrieres');
        
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'indice', 'corps', 'etablissement', 'salaire_base', 
                'montant_bonifications', 'position_administrative', 'taux_cotisation'
            ]);
        });

        Schema::table('retraites', function (Blueprint $table) {
            $table->dropColumn([
                'indice_retraite', 'corps', 'duree_service_mois', 'taux_liquidation',
                'salaire_reference', 'pension_base', 'bonifications_totales'
            ]);
        });
    }
};