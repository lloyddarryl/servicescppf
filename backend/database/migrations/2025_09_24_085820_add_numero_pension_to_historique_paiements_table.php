<?php
// database/migrations/2025_09_24_000002_add_numero_pension_to_historique_paiements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historique_paiements', function (Blueprint $table) {
            $table->string('numero_pension')->nullable()->after('retraite_id');
            $table->index('numero_pension');
        });
    }

    public function down(): void
    {
        Schema::table('historique_paiements', function (Blueprint $table) {
            $table->dropIndex(['numero_pension']);
            $table->dropColumn('numero_pension');
        });
    }
};