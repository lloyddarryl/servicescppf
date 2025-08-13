<?php
// database/migrations/2024_08_07_create_reclamations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            
            // Références utilisateur
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // 'agent' ou 'retraite'
            $table->string('user_email');
            $table->string('user_telephone')->nullable();
            
            // Détails de la réclamation
            $table->string('numero_reclamation')->unique();
            $table->string('type_reclamation');
            $table->string('sujet_personnalise')->nullable();
            $table->text('description');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            
            // Statut et suivi
            $table->enum('statut', [
                'en_attente', 'en_cours', 'en_revision', 
                'resolu', 'ferme', 'rejete'
            ])->default('en_attente');
            
            // Documents
            $table->boolean('necessite_document')->default(false);
            $table->json('documents')->nullable(); // Stockage des chemins des documents
            
            // Timestamps et suivi
            $table->timestamp('date_soumission');
            $table->timestamp('date_derniere_mise_a_jour')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->text('commentaires_admin')->nullable();
            $table->string('traite_par')->nullable();
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['user_id', 'user_type']);
            $table->index(['statut']);
            $table->index(['type_reclamation']);
            $table->index(['date_soumission']);
        });
        
        // Table pour l'historique des statuts
        Schema::create('reclamation_historique', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')->constrained()->onDelete('cascade');
            $table->enum('ancien_statut', [
                'en_attente', 'en_cours', 'en_revision', 
                'resolu', 'ferme', 'rejete'
            ]);
            $table->enum('nouveau_statut', [
                'en_attente', 'en_cours', 'en_revision', 
                'resolu', 'ferme', 'rejete'
            ]);
            $table->text('commentaire')->nullable();
            $table->string('modifie_par')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reclamation_historique');
        Schema::dropIfExists('reclamations');
    }
};