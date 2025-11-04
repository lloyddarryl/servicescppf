<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            
            // Informations du template
            $table->string('code')->unique(); // Code unique pour identifier le template
            $table->string('titre');
            $table->text('contenu');
            $table->string('categorie'); // 'question', 'reponse_admin', 'automatique'
            
            // Visibilité
            $table->enum('visible_pour', ['user', 'admin', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            
            // Métadonnées
            $table->integer('ordre')->default(0); // Ordre d'affichage
            $table->string('icon')->nullable(); // Emoji ou icône
            
            $table->timestamps();
            
            // Index
            $table->index('categorie');
            $table->index('is_active');
        });
        
        // Insérer des templates par défaut
        DB::table('message_templates')->insert([
            // Templates pour utilisateurs
            [
                'code' => 'question_pension',
                'titre' => 'Question sur ma pension',
                'contenu' => 'Bonjour, j\'ai une question concernant ma pension de retraite. Pouvez-vous m\'aider ?',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 1,
                'icon' => '💰',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'question_cotisation',
                'titre' => 'Question sur mes cotisations',
                'contenu' => 'Bonjour, j\'aimerais obtenir des informations sur mes cotisations. Merci.',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 2,
                'icon' => '📊',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'probleme_connexion',
                'titre' => 'Problème de connexion',
                'contenu' => 'Bonjour, je rencontre des difficultés pour me connecter à mon compte. Pouvez-vous m\'assister ?',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 3,
                'icon' => '🔐',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'demande_document',
                'titre' => 'Demande de document',
                'contenu' => 'Bonjour, je souhaiterais obtenir un document. Pouvez-vous m\'indiquer la procédure à suivre ?',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 4,
                'icon' => '📄',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'rdv_annulation',
                'titre' => 'Annulation de rendez-vous',
                'contenu' => 'Bonjour, je souhaite annuler mon rendez-vous prévu. Pouvez-vous m\'aider ?',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 5,
                'icon' => '📅',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reclamation',
                'titre' => 'Faire une réclamation',
                'contenu' => 'Bonjour, je souhaite faire une réclamation concernant...',
                'categorie' => 'question',
                'visible_pour' => 'user',
                'ordre' => 6,
                'icon' => '📢',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Templates de réponses pour admins
            [
                'code' => 'reponse_passer_caisse',
                'titre' => 'Passer à la caisse',
                'contenu' => 'Bonjour, pour traiter votre demande, nous vous invitons à vous présenter à nos caisses avec les documents nécessaires. Nos horaires d\'ouverture sont du lundi au vendredi de 8h à 16h.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 1,
                'icon' => '🏢',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_rdv_requis',
                'titre' => 'Rendez-vous requis',
                'contenu' => 'Bonjour, pour traiter votre demande, nous vous invitons à prendre rendez-vous via la plateforme. Cela nous permettra de vous accueillir dans les meilleures conditions.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 2,
                'icon' => '📅',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_documents_manquants',
                'titre' => 'Documents manquants',
                'contenu' => 'Bonjour, votre dossier nécessite des documents complémentaires. Veuillez les télécharger via votre espace personnel ou les déposer à nos guichets.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 3,
                'icon' => '📎',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_en_cours_traitement',
                'titre' => 'Traitement en cours',
                'contenu' => 'Bonjour, votre demande est actuellement en cours de traitement. Nous vous tiendrons informé(e) de l\'avancement dans les plus brefs délais.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 4,
                'icon' => '⏳',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_demande_incomplete',
                'titre' => 'Demande incomplète',
                'contenu' => 'Bonjour, votre demande ne peut être traitée car elle est incomplète. Merci de nous fournir les informations manquantes pour que nous puissions poursuivre.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 5,
                'icon' => '⚠️',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_hors_competence',
                'titre' => 'Hors compétence',
                'contenu' => 'Bonjour, votre demande ne relève pas de nos compétences. Nous vous invitons à contacter le service approprié pour obtenir l\'assistance nécessaire.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 6,
                'icon' => '🔀',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reponse_bien_recu',
                'titre' => 'Bien reçu',
                'contenu' => 'Bonjour, nous avons bien reçu votre message et nous allons y donner suite dans les meilleurs délais. Merci pour votre patience.',
                'categorie' => 'reponse_admin',
                'visible_pour' => 'admin',
                'ordre' => 7,
                'icon' => '✅',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('message_templates');
    }
};