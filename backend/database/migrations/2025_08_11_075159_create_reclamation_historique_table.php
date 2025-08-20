<?php

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
        // Vérifier si la table n'existe pas déjà
        if (!Schema::hasTable('reclamation_historique')) {
            Schema::create('reclamation_historique', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reclamation_id');
                $table->string('ancien_statut')->nullable();
                $table->string('nouveau_statut');
                $table->text('commentaire')->nullable();
                $table->string('modifie_par')->nullable();
                $table->timestamps();
                
                // Index pour les performances
                $table->index('reclamation_id');
                
                // Clé étrangère vers reclamations avec cascade
                $table->foreign('reclamation_id')
                      ->references('id')
                      ->on('reclamations')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation_historique');
    }
};