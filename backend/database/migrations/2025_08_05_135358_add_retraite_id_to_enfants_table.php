<?php
// Fichier: database/migrations/2024_xx_xx_add_retraite_id_to_enfants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enfants', function (Blueprint $table) {
            // Ajouter la colonne retraite_id
            $table->unsignedBigInteger('retraite_id')->nullable()->after('agent_id');
            
            // Ajouter la clé étrangère
            $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
            
            // Permettre à agent_id d'être nullable aussi
            $table->unsignedBigInteger('agent_id')->nullable()->change();
            
            // Ajouter un index pour les performances
            $table->index(['retraite_id', 'actif']);
        });
    }

    public function down()
    {
        Schema::table('enfants', function (Blueprint $table) {
            $table->dropForeign(['retraite_id']);
            $table->dropIndex(['retraite_id', 'actif']);
            $table->dropColumn('retraite_id');
        });
    }
};