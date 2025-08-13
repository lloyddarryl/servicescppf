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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('matricule_solde', 13)->unique(); // 7 ou 13 caractères
            $table->string('nom');
            $table->string('prenoms');
            $table->string('poste');
            $table->string('direction');
            $table->string('grade')->nullable();
            $table->date('date_prise_service')->nullable();
            
            // Authentification
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('password')->nullable(); // null jusqu'à la création du vrai mdp
            $table->boolean('first_login')->default(true);
            $table->boolean('password_changed')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // Status
            $table->enum('status', ['actif', 'suspendu', 'transfere'])->default('actif');
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['matricule_solde', 'status']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};