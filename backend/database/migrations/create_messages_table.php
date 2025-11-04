<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Relation avec la conversation
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            
            // Expéditeur (polymorphique)
            $table->string('sender_type'); // 'admin', 'agent', 'retraite'
            $table->unsignedBigInteger('sender_id');
            
            // Contenu du message
            $table->text('message');
            $table->boolean('is_template')->default(false); // Message pré-rempli
            $table->string('template_type')->nullable(); // Type de template utilisé
            
            // Pièces jointes
            $table->json('attachments')->nullable(); // [{name, path, type, size}]
            
            // Statut de lecture
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Métadonnées
            $table->boolean('is_system_message')->default(false); // Messages système (ex: "Conversation fermée")
            $table->string('ip_address')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
            $table->index('is_read');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};