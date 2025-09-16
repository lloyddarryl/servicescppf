<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarrieresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrieres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->integer('numero_ordre')->nullable(); // NumeroOrdre
            $table->date('date_debut')->nullable(); // DateDebut
            $table->date('date_fin')->nullable(); // DateFin
            $table->string('position')->nullable(); // Position
            $table->string('etablissement')->nullable(); // Etablissement
            $table->string('corps')->nullable(); // Corps
            $table->integer('grade')->nullable(); // Grade
            $table->integer('indice')->nullable(); // Indice
            $table->decimal('retenue', 10, 2)->nullable(); // Retenue
            $table->integer('nombre_mois')->nullable(); // NombreMois
            $table->integer('regime')->nullable(); // Regime
            $table->string('sous_regime')->nullable(); // Sous-regime
            $table->decimal('annuite', 8, 4)->nullable(); // Annuite (corrigé)
            $table->decimal('total_cotisations', 12, 2)->nullable(); // Total des cotisations
            $table->enum('statut', ['VALIDE', 'EN_ATTENTE', 'INVALIDE'])->default('VALIDE');
            $table->text('observations')->nullable();
            $table->timestamps();
            
            // Index et contraintes
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->index(['agent_id', 'date_debut']);
            $table->index(['agent_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carrieres');
    }
}