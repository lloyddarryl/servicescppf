<?php
// File: database/migrations/2025_07_30_140000_add_pension_simulation_fields_corrected.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Vérifier et ajouter les champs manquants à la table agents
        if (!Schema::hasColumn('agents', 'indice')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->integer('indice')->nullable()->after('grade');
            });
        }

        if (!Schema::hasColumn('agents', 'corps')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('corps')->nullable()->after('indice');
            });
        }

        if (!Schema::hasColumn('agents', 'etablissement')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('etablissement')->nullable()->after('direction');
            });
        }

        if (!Schema::hasColumn('agents', 'salaire_base')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->decimal('salaire_base', 12, 2)->nullable()->after('corps');
            });
        }

        if (!Schema::hasColumn('agents', 'montant_bonifications')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->decimal('montant_bonifications', 12, 2)->default(0)->after('salaire_base');
            });
        }

        if (!Schema::hasColumn('agents', 'position_administrative')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('position_administrative')->default('ACTIVITE')->after('status');
            });
        }

        if (!Schema::hasColumn('agents', 'taux_cotisation')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->decimal('taux_cotisation', 5, 2)->default(6.00)->after('montant_bonifications');
            });
        }

        // Ajouter les champs manquants à la table retraites
        if (!Schema::hasColumn('retraites', 'indice_retraite')) {
            Schema::table('retraites', function (Blueprint $table) {
                $table->integer('indice_retraite')->nullable()->after('montant_pension');
                $table->string('corps')->nullable()->after('indice_retraite');
                $table->decimal('duree_service_mois', 8, 2)->nullable()->after('date_retraite');
                $table->decimal('taux_liquidation', 5, 2)->nullable()->after('duree_service_mois');
                $table->decimal('salaire_reference', 12, 2)->nullable()->after('taux_liquidation');
                $table->decimal('pension_base', 12, 2)->nullable()->after('salaire_reference');
                $table->decimal('bonifications_totales', 12, 2)->default(0)->after('pension_base');
            });
        }

        // Créer les nouvelles tables uniquement si elles n'existent pas
        if (!Schema::hasTable('carrieres')) {
            Schema::create('carrieres', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id');
                $table->string('matricule_assure');
                $table->integer('grade_code');
                $table->string('grade_libelle')->nullable();
                $table->integer('indice');
                $table->string('statut');
                $table->string('position_administrative');
                $table->string('presence')->default('PRESENT');
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
                $table->date('date_carriere');
                $table->string('etat_general')->nullable();
                $table->decimal('taux_cotisation', 5, 2)->default(6.00);
                $table->boolean('valide')->default(true);
                $table->timestamps();

                $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
                $table->index(['agent_id', 'date_carriere']);
                $table->index('matricule_assure');
            });
        }

        // Créer la table grilles_indiciaires
        if (!Schema::hasTable('grilles_indiciaires')) {
            Schema::create('grilles_indiciaires', function (Blueprint $table) {
                $table->id();
                $table->string('type_grille');
                $table->string('categorie');
                $table->integer('classe')->nullable();
                $table->integer('duree_classe')->nullable();
                $table->integer('indice_ancien')->nullable();
                $table->integer('indice_nouveau')->nullable();
                $table->decimal('valeur_point', 8, 2)->default(500.00);
                $table->timestamps();

                $table->index(['type_grille', 'categorie', 'classe']);
                $table->index('indice_nouveau');
            });
        }

        // Créer la table parametres_pension
        if (!Schema::hasTable('parametres_pension')) {
            Schema::create('parametres_pension', function (Blueprint $table) {
                $table->id();
                $table->string('code_parametre')->unique();
                $table->string('libelle');
                $table->decimal('valeur', 10, 4);
                $table->string('unite')->nullable();
                $table->text('description')->nullable();
                $table->date('date_effet');
                $table->date('date_fin')->nullable();
                $table->boolean('actif')->default(true);
                $table->string('version_reglementation')->default('Article_94');
                $table->timestamps();
            });
        }

        // Créer la table simulations_pension
        if (!Schema::hasTable('simulations_pension')) {
            Schema::create('simulations_pension', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id');
                $table->date('date_simulation');
                $table->date('date_retraite_prevue');
                $table->decimal('duree_service_simulee', 8, 2);
                $table->integer('indice_simule');
                $table->decimal('salaire_reference', 12, 2);
                $table->decimal('taux_liquidation', 5, 2);
                $table->decimal('pension_base', 12, 2);
                $table->decimal('bonifications', 12, 2)->default(0);
                $table->decimal('pension_totale', 12, 2);
                $table->decimal('coefficient_temporel', 5, 2)->nullable();
                $table->decimal('pension_apres_coefficient', 12, 2)->nullable();
                $table->integer('annee_pension')->nullable();
                $table->string('methode_calcul')->default('Article_94');
                $table->json('parametres_utilises')->nullable();
                $table->timestamps();

                $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
                $table->index(['agent_id', 'date_simulation']);
            });
        }

        // Créer la table coefficients_temporels
        if (!Schema::hasTable('coefficients_temporels')) {
            Schema::create('coefficients_temporels', function (Blueprint $table) {
                $table->id();
                $table->integer('annee')->unique();
                $table->decimal('coefficient', 5, 2);
                $table->string('periode_debut');
                $table->string('periode_fin');
                $table->text('description')->nullable();
                $table->boolean('actif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        // Supprimer les tables dans l'ordre inverse
        Schema::dropIfExists('coefficients_temporels');
        Schema::dropIfExists('simulations_pension');
        Schema::dropIfExists('parametres_pension');
        Schema::dropIfExists('grilles_indiciaires');
        Schema::dropIfExists('carrieres');
        
        // Supprimer les colonnes ajoutées (seulement si elles existent)
        Schema::table('agents', function (Blueprint $table) {
            $columnsToCheck = ['indice', 'corps', 'etablissement', 'salaire_base', 'montant_bonifications', 'position_administrative', 'taux_cotisation'];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('agents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('retraites', function (Blueprint $table) {
            $columnsToCheck = ['indice_retraite', 'corps', 'duree_service_mois', 'taux_liquidation', 'salaire_reference', 'pension_base', 'bonifications_totales'];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('retraites', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};