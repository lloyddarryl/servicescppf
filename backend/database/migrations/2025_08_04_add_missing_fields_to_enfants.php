<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enfants', function (Blueprint $table) {
            // Ajoutez uniquement les colonnes manquantes
            if (!Schema::hasColumn('enfants', 'prestation_familiale')) {
                $table->boolean('prestation_familiale')->default(false);
            }
            if (!Schema::hasColumn('enfants', 'scolarise')) {
                $table->boolean('scolarise')->default(false);
            }
            if (!Schema::hasColumn('enfants', 'niveau_scolaire')) {
                $table->string('niveau_scolaire')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('enfants', function (Blueprint $table) {
            $table->dropColumn([
                'prestation_familiale',
                'scolarise',
                'niveau_scolaire'
            ]);
        });
    }
};