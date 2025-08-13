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
        Schema::create('parametres_pension', function (Blueprint $table) {
            $table->id();
            $table->string('code_parametre', 50)->unique();
            $table->string('libelle');
            $table->decimal('valeur', 15, 4);
            $table->enum('type_valeur', ['decimal', 'percentage', 'integer', 'boolean'])->default('decimal');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('date_effet')->default(now());
            $table->date('date_fin')->nullable();
            $table->timestamps();
            
            // Index pour les recherches fréquentes
            $table->index(['code_parametre', 'is_active']);
            $table->index(['date_effet', 'date_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_pension');
    }
};