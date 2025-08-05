<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnfantsTable extends Migration
{
    public function up()
    {
        Schema::create('enfants', function (Blueprint $table) {
            $table->id();
            $table->string('enfant_id');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('cascade');
            $table->foreignId('retraite_id')->nullable()->constrained('retraites')->onDelete('cascade');
            $table->string('nom');
            $table->string('prenoms');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->boolean('prestation_familiale')->default(false);
            $table->boolean('scolarise')->default(false);
            $table->string('niveau_scolaire')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }
}