<?php
// Fichier: backend/database/migrations/xxxx_xx_xx_add_verification_fields_to_retraites_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('retraites', function (Blueprint $table) {
            // Ajouter les champs manquants pour la vérification SMS
            if (!Schema::hasColumn('retraites', 'verification_code')) {
                $table->string('verification_code', 6)->nullable()->after('password');
            }
            if (!Schema::hasColumn('retraites', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
            if (!Schema::hasColumn('retraites', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])->default('M')->after('prenoms');
            }
            if (!Schema::hasColumn('retraites', 'situation_matrimoniale')) {
                $table->string('situation_matrimoniale')->nullable()->after('sexe');
            }
        });
    }

    public function down()
    {
        Schema::table('retraites', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                'sexe',
                'situation_matrimoniale'
            ]);
        });
    }
};