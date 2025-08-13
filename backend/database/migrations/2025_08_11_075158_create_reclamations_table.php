<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_create_reclamations_table.php
return new class extends Migration
{
    public function up()
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['agent', 'retraite']);
            $table->string('user_email');
            $table->string('user_telephone')->nullable();
            $table->string('numero_reclamation')->unique();
            $table->string('type_reclamation');
            $table->string('sujet_personnalise')->nullable();
            $table->text('description');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->enum('statut', ['en_attente', 'en_cours', 'en_revision', 'resolu', 'ferme', 'rejete'])->default('en_attente');
            $table->boolean('necessite_document')->default(false);
            $table->json('documents')->nullable();
            $table->timestamp('date_soumission');
            $table->timestamp('date_derniere_mise_a_jour')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->text('commentaires_admin')->nullable();
            $table->string('traite_par')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'user_type']);
            $table->index('statut');
            $table->index('type_reclamation');
        });
    }
};
