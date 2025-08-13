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
        Schema::create('retraites', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pension')->unique(); // Uniquement des chiffres, pas de limite
            $table->string('nom');
            $table->string('prenoms');
            $table->date('date_naissance');
            $table->date('date_retraite');
            $table->string('ancien_poste');
            $table->string('ancienne_direction');
            $table->text('parcours_professionnel')->nullable();
            $table->decimal('montant_pension', 15, 2)->nullable();
            
            // Authentification
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('password')->nullable(); // null jusqu'à la création du vrai mdp
            $table->boolean('first_login')->default(true);
            $table->boolean('password_changed')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // Status
            $table->enum('status', ['actif', 'suspendu', 'decede'])->default('actif');
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['numero_pension', 'status']);
            $table->index('email');
            $table->index('date_naissance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retraites');
    }
};