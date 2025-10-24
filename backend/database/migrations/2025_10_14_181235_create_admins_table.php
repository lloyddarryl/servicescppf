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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telephone')->nullable();
            $table->enum('role', ['admin1', 'admin2', 'super_admin'])->default('admin1');
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->timestamp('derniere_connexion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Index pour les recherches fréquentes
            $table->index(['email', 'statut']);
            $table->index('role');
            
            // Clé étrangère pour created_by
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};