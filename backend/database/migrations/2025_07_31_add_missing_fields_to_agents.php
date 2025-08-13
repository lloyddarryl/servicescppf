<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'date_naissance')) {
                $table->date('date_naissance')->nullable();
            }
            if (!Schema::hasColumn('agents', 'situation_matrimoniale')) {
                $table->string('situation_matrimoniale')->nullable();
            }
            if (!Schema::hasColumn('agents', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])->default('M');
            }
            if (!Schema::hasColumn('agents', 'corps')) {
                $table->string('corps')->nullable();
            }
            if (!Schema::hasColumn('agents', 'etablissement')) {
                $table->string('etablissement')->nullable();
            }
            if (!Schema::hasColumn('agents', 'indice')) {
                $table->integer('indice')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'date_naissance',
                'situation_matrimoniale',
                'sexe',
                'corps',
                'etablissement',
                'indice'
            ]);
        });
    }
};