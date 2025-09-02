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
        Schema::create('documents_retraites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retraite_id');
            $table->string('nom_original');
            $table->string('nom_fichier'); // nom stocké sur le serveur
            $table->string('chemin_fichier');
            $table->enum('type_document', ['certificat_vie', 'autre']);
            $table->string('description')->nullable(); // pour le type "autre"
            $table->integer('taille_fichier'); // en bytes
            $table->string('extension');
            $table->enum('statut', ['actif', 'expire', 'remplace', 'supprime'])->default('actif');
            
            // Spécifique aux certificats de vie
            $table->date('date_emission')->nullable();
            $table->date('date_expiration')->nullable(); // calculée automatiquement pour certificats de vie
            $table->string('autorite_emission')->nullable(); // Mairie, Préfecture, etc.
            
            // Suivi
            $table->timestamp('date_depot');
            $table->boolean('notifie_par_email')->default(false);
            $table->json('metadata')->nullable(); // infos supplémentaires
            
            $table->timestamps();
            
            // Index et contraintes
            $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
            $table->index(['retraite_id', 'type_document']);
            $table->index(['type_document', 'statut']);
            $table->index(['date_expiration', 'statut']); // Pour les notifications d'expiration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_retraites');
    }
};