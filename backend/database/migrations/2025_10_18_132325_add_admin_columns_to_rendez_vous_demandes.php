<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rendez_vous_demandes', function (Blueprint $table) {
            // Vérifier et ajouter admin_id seulement s'il n'existe pas
            if (!Schema::hasColumn('rendez_vous_demandes', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            }
            
            // Vérifier et ajouter date_traitement seulement s'il n'existe pas
            if (!Schema::hasColumn('rendez_vous_demandes', 'date_traitement')) {
                $table->timestamp('date_traitement')->nullable()->after('admin_id');
            }
            
            // Vérifier et ajouter commentaire_admin seulement s'il n'existe pas
            if (!Schema::hasColumn('rendez_vous_demandes', 'commentaire_admin')) {
                $table->text('commentaire_admin')->nullable()->after('date_traitement');
            }
        });

        // Ajouter la clé étrangère seulement si elle n'existe pas
        if (!$this->foreignKeyExists('rendez_vous_demandes', 'rendez_vous_demandes_admin_id_foreign')) {
            Schema::table('rendez_vous_demandes', function (Blueprint $table) {
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::table('rendez_vous_demandes', function (Blueprint $table) {
            // Supprimer la clé étrangère si elle existe
            if ($this->foreignKeyExists('rendez_vous_demandes', 'rendez_vous_demandes_admin_id_foreign')) {
                $table->dropForeign(['admin_id']);
            }
            
            // Supprimer les colonnes si elles existent
            $columnsToCheck = ['commentaire_admin', 'date_traitement'];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('rendez_vous_demandes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Vérifier si une clé étrangère existe
     */
    private function foreignKeyExists($table, $name)
    {
        $foreignKeys = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $name]);
        
        return count($foreignKeys) > 0;
    }
};