<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Participants (polymorphique pour gérer Agent et Retraite)
            $table->string('user_type'); // 'agent' ou 'retraite'
            $table->unsignedBigInteger('user_id');
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            
            // Informations de la conversation
            $table->string('sujet')->nullable(); // Sujet optionnel
            $table->enum('statut', ['ouvert', 'en_cours', 'resolu', 'ferme'])->default('ouvert');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->string('categorie')->nullable(); // 'reclamation', 'question', 'demande', etc.
            
            // Ticket système
            $table->string('numero_ticket')->unique();
            
            // Métadonnées
            $table->timestamp('derniere_activite')->nullable();
            $table->timestamp('resolu_le')->nullable();
            $table->unsignedBigInteger('resolu_par')->nullable(); // admin_id
            $table->text('notes_internes')->nullable(); // Notes visibles uniquement par les admins
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['user_type', 'user_id']);
            $table->index('statut');
            $table->index('numero_ticket');
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};