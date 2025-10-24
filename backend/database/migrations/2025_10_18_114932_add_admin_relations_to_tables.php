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
        // Ajouter admin_id à la table rendez_vous_demandes
        if (!Schema::hasColumn('rendez_vous_demandes', 'admin_id')) {
            Schema::table('rendez_vous_demandes', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->text('commentaire_admin')->nullable()->after('admin_id');
                $table->timestamp('date_traitement')->nullable()->after('commentaire_admin');
                
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            });
        }

        // Ajouter admin_id à la table reclamations
        if (!Schema::hasColumn('reclamations', 'admin_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->text('reponse_admin')->nullable()->after('admin_id');
                $table->timestamp('date_traitement')->nullable()->after('reponse_admin');
                
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            });
        }

        // Ajouter admin_id à la table documents_retraites (si elle existe)
        if (Schema::hasTable('documents_retraites') && !Schema::hasColumn('documents_retraites', 'admin_id')) {
            Schema::table('documents_retraites', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->text('commentaires_admin')->nullable()->after('admin_id');
                $table->text('motif_rejet')->nullable()->after('commentaires_admin');
                $table->timestamp('date_traitement')->nullable()->after('motif_rejet');
                
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            });
        }

        // Créer la table message_dashboards si elle n'existe pas
        if (!Schema::hasTable('message_dashboards')) {
            Schema::create('message_dashboards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id');
                $table->string('user_type'); // 'agent' ou 'retraite'
                $table->unsignedBigInteger('user_id');
                $table->string('titre');
                $table->text('message');
                $table->enum('type', ['info', 'warning', 'success', 'error'])->default('info');
                $table->enum('statut', ['envoye', 'lu', 'archive'])->default('envoye');
                $table->timestamp('date_lecture')->nullable();
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
                $table->timestamp('expire_le')->nullable();
                $table->timestamps();
                
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
                $table->index(['user_type', 'user_id']);
                $table->index('statut');
            });
        }

        // Créer la table log_activites si elle n'existe pas
        if (!Schema::hasTable('log_activites')) {
            Schema::create('log_activites', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id');
                $table->string('action');
                $table->text('description');
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('donnees_avant')->nullable();
                $table->json('donnees_apres')->nullable();
                $table->enum('resultat', ['success', 'error', 'warning'])->default('success');
                $table->timestamps();
                
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
                $table->index('action');
                $table->index('admin_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes ajoutées aux tables existantes
        if (Schema::hasColumn('rendez_vous_demandes', 'admin_id')) {
            Schema::table('rendez_vous_demandes', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn(['admin_id', 'commentaire_admin', 'date_traitement']);
            });
        }

        if (Schema::hasColumn('reclamations', 'admin_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn(['admin_id', 'reponse_admin', 'date_traitement']);
            });
        }

        if (Schema::hasTable('documents_retraites') && Schema::hasColumn('documents_retraites', 'admin_id')) {
            Schema::table('documents_retraites', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn(['admin_id', 'commentaires_admin', 'motif_rejet', 'date_traitement']);
            });
        }

        // Supprimer les nouvelles tables
        Schema::dropIfExists('log_activites');
        Schema::dropIfExists('message_dashboards');
    }
};