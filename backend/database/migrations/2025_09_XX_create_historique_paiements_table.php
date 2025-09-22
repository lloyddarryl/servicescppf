<?php
// database/migrations/XXXX_XX_XX_create_historique_paiements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('historique_paiements', function (Blueprint $table) {
            $table->id();
            
            // Relation avec le retraité
            $table->unsignedBigInteger('retraite_id');
            $table->foreign('retraite_id')->references('id')->on('retraites')->onDelete('cascade');
            
            // Champs selon votre PDF
            $table->string('numero_titre')->unique(); // N° titre : uniquement des chiffres
            $table->string('type_paiement', 10)->default('D'); // Type : D
            $table->date('date_paiement'); // Date du versement
            
            // Informations bénéficiaire
            $table->string('nom_beneficiaire');
            $table->string('prenoms_beneficiaire');
            $table->string('complement_nom')->nullable();
            
            // Détails selon le PDF réel
            $table->string('regime')->default('MAS'); // MAS selon le PDF
            $table->string('disponibilite')->default('Bancaire'); // Bancaire selon le PDF
            $table->string('mode_reglement'); // AFG Bank LIBREVILLE, etc.
            
            // Montants selon le PDF (avec rappels et retenues)
            $table->decimal('montant_net', 15, 2); // Montant net
            $table->decimal('montant_brut', 15, 2); // Montant brut (supérieur au net)
            $table->decimal('rappels', 15, 2)->default(0); // Rappels
            $table->decimal('retenues', 15, 2)->default(0); // Retenues
            
            // Informations complémentaires
            $table->string('etat_paiement')->default('Versé'); 
            $table->string('dossier_pension')->nullable(); // N° dossier si nécessaire
            $table->text('observations')->nullable();
            
            // Audit
            $table->timestamps();
            
            // Index
            $table->index(['retraite_id', 'date_paiement']);
            $table->index('date_paiement');
            $table->index('numero_titre');
        });
    }

    public function down()
    {
        Schema::dropIfExists('historique_paiements');
    }
};