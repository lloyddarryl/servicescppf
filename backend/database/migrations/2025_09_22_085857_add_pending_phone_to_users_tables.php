<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Ajouter à la table agents
        Schema::table('agents', function (Blueprint $table) {
            $table->string('pending_phone_number')->nullable()->after('telephone');
        });

        // Ajouter à la table retraites
        Schema::table('retraites', function (Blueprint $table) {
            $table->string('pending_phone_number')->nullable()->after('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('pending_phone_number');
        });

        Schema::table('retraites', function (Blueprint $table) {
            $table->dropColumn('pending_phone_number');
        });
    }
};