<?php
// File: database/migrations/2025_01_XX_extend_famille_to_retraites.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Modifier la table conjoints pour supporter les retraités
        Schema::table('conjoints', function (Blueprint $table) {
            // Ajouter colonne pour les retraités
            $table->unsignedBigInteger('retraite_id')->nullable()->after('agent_id');
            
            // Modifier la contrainte agent_id pour être nullable
            $table->unsignedBigInteger('agent_id')->nullable()->change();
            
            // Ajouter la clé étrangère pour retraites
            $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
            $table->index('retraite_id');
        });

        // Modifier la table enfants pour supporter les retraités
        Schema::table('enfants', function (Blueprint $table) {
            // Ajouter colonne pour les retraités
            $table->unsignedBigInteger('retraite_id')->nullable()->after('agent_id');
            
            // Modifier la contrainte agent_id pour être nullable
            $table->unsignedBigInteger('agent_id')->nullable()->change();
            
            // Ajouter la clé étrangère pour retraites
            $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
            $table->index('retraite_id');
        });

        // Ajouter contrainte : un conjoint/enfant doit être lié soit à un agent, soit à un retraité
        DB::statement('
            ALTER TABLE conjoints ADD CONSTRAINT chk_conjoint_parent 
            CHECK ((agent_id IS NOT NULL AND retraite_id IS NULL) OR (agent_id IS NULL AND retraite_id IS NOT NULL))
        ');

        DB::statement('
            ALTER TABLE enfants ADD CONSTRAINT chk_enfant_parent 
            CHECK ((agent_id IS NOT NULL AND retraite_id IS NULL) OR (agent_id IS NULL AND retraite_id IS NOT NULL))
        ');
    }

    public function down()
    {
        // Supprimer les contraintes
        DB::statement('ALTER TABLE conjoints DROP CONSTRAINT IF EXISTS chk_conjoint_parent');
        DB::statement('ALTER TABLE enfants DROP CONSTRAINT IF EXISTS chk_enfant_parent');

        // Supprimer les colonnes et clés étrangères
        Schema::table('conjoints', function (Blueprint $table) {
            $table->dropForeign(['retraite_id']);
            $table->dropColumn('retraite_id');
            $table->unsignedBigInteger('agent_id')->nullable(false)->change();
        });

        Schema::table('enfants', function (Blueprint $table) {
            $table->dropForeign(['retraite_id']);
            $table->dropColumn('retraite_id');
            $table->unsignedBigInteger('agent_id')->nullable(false)->change();
        });
    }
};