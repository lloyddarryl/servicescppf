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
        Schema::create('messages_dashboard', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['agent', 'retraite']);
            $table->string('titre');
            $table->text('message');
            $table->enum('type_message', ['info', 'alerte', 'urgent', 'notification'])->default('info');
            $table->enum('priorite', ['normale', 'haute', 'urgente'])->default('normale');
            $table->enum('statut', ['non_lu', 'lu', 'archive'])->default('non_lu');
            $table->timestamp('date_lecture')->nullable();
            $table->timestamp('expire_le')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Clés étrangères
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            
            // Index pour les performances
            $table->index(['user_id', 'user_type']);
            $table->index(['statut', 'created_at']);
            $table->index(['type_message', 'priorite']);
            $table->index('expire_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages_dashboard');
    }
};