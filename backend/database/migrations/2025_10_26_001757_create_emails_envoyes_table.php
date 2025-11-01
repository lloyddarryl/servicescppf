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
        Schema::create('emails_envoyes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::create('emails_envoyes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('retraite_id')->constrained();
    $table->string('type'); // 'rappel_certificat'
    $table->string('destinataire');
    $table->timestamp('envoye_le');
    $table->boolean('ouvert')->default(false);
    $table->timestamp('ouvert_le')->nullable();
    $table->timestamps();
});    }
};
