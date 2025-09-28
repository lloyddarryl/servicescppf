<?php
// database/migrations/2025_09_24_000001_add_matricule_solde_to_carrieres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrieres', function (Blueprint $table) {
            $table->string('matricule_solde', 13)->nullable()->after('agent_id');
            $table->index('matricule_solde');
        });
    }

    public function down(): void
    {
        Schema::table('carrieres', function (Blueprint $table) {
            $table->dropIndex(['matricule_solde']);
            $table->dropColumn('matricule_solde');
        });
    }
};