<?php
// Fichier: database/migrations/xxxx_xx_xx_ensure_retraite_support_in_famille_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ✅ 1. S'assurer que la table retraites a tous les champs nécessaires
        Schema::table('retraites', function (Blueprint $table) {
            if (!Schema::hasColumn('retraites', 'verification_code')) {
                $table->string('verification_code', 6)->nullable()->after('password');
            }
            if (!Schema::hasColumn('retraites', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
            if (!Schema::hasColumn('retraites', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])->default('M')->after('prenoms');
            }
            if (!Schema::hasColumn('retraites', 'situation_matrimoniale')) {
                $table->string('situation_matrimoniale')->nullable()->after('sexe');
            }
        });

        // ✅ 2. S'assurer que la table conjoints supporte les retraités
        Schema::table('conjoints', function (Blueprint $table) {
            // Ajouter retraite_id si pas présent
            if (!Schema::hasColumn('conjoints', 'retraite_id')) {
                $table->unsignedBigInteger('retraite_id')->nullable()->after('agent_id');
            }
            
            // Modifier agent_id pour être nullable
            if (Schema::hasColumn('conjoints', 'agent_id')) {
                DB::statement('ALTER TABLE conjoints MODIFY agent_id BIGINT UNSIGNED NULL');
            }
        });

        // ✅ 3. S'assurer que la table enfants supporte les retraités
        Schema::table('enfants', function (Blueprint $table) {
            // Ajouter retraite_id si pas présent
            if (!Schema::hasColumn('enfants', 'retraite_id')) {
                $table->unsignedBigInteger('retraite_id')->nullable()->after('agent_id');
            }
            
            // Modifier agent_id pour être nullable
            if (Schema::hasColumn('enfants', 'agent_id')) {
                DB::statement('ALTER TABLE enfants MODIFY agent_id BIGINT UNSIGNED NULL');
            }
        });

        // ✅ 4. Ajouter les clés étrangères si elles n'existent pas
        try {
            Schema::table('conjoints', function (Blueprint $table) {
                // Vérifier si la contrainte existe déjà
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'conjoints' 
                    AND COLUMN_NAME = 'retraite_id' 
                    AND CONSTRAINT_NAME LIKE '%_foreign'
                ");
                
                if (empty($foreignKeys)) {
                    $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
                }
            });
        } catch (\Exception $e) {
            // La contrainte existe déjà, on continue
            echo "Contrainte retraite_id pour conjoints existe déjà\n";
        }

        try {
            Schema::table('enfants', function (Blueprint $table) {
                // Vérifier si la contrainte existe déjà
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'enfants' 
                    AND COLUMN_NAME = 'retraite_id' 
                    AND CONSTRAINT_NAME LIKE '%_foreign'
                ");
                
                if (empty($foreignKeys)) {
                    $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
                }
            });
        } catch (\Exception $e) {
            // La contrainte existe déjà, on continue
            echo "Contrainte retraite_id pour enfants existe déjà\n";
        }

        // ✅ 5. Ajouter des index pour optimiser les requêtes
        try {
            Schema::table('conjoints', function (Blueprint $table) {
                $table->index(['retraite_id', 'statut'], 'conjoints_retraite_statut_index');
            });
        } catch (\Exception $e) {
            // Index existe déjà
        }

        try {
            Schema::table('enfants', function (Blueprint $table) {
                $table->index(['retraite_id', 'actif'], 'enfants_retraite_actif_index');
            });
        } catch (\Exception $e) {
            // Index existe déjà
        }

        echo "✅ Migration terminée - Support des retraités ajouté\n";
    }

    public function down()
    {
        // Supprimer les index
        Schema::table('conjoints', function (Blueprint $table) {
            $table->dropIndex('conjoints_retraite_statut_index');
        });

        Schema::table('enfants', function (Blueprint $table) {
            $table->dropIndex('enfants_retraite_actif_index');
        });

        // Supprimer les clés étrangères
        Schema::table('conjoints', function (Blueprint $table) {
            $table->dropForeign(['retraite_id']);
            $table->dropColumn('retraite_id');
        });

        Schema::table('enfants', function (Blueprint $table) {
            $table->dropForeign(['retraite_id']);
            $table->dropColumn('retraite_id');
        });

        // Supprimer les nouveaux champs de retraites
        Schema::table('retraites', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                'sexe',
                'situation_matrimoniale'
            ]);
        });
    }
};