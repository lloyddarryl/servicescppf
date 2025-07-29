<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
        });

        Schema::table('retraites', function (Blueprint $table) {
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['verification_code', 'verification_code_expires_at', 'phone_verified_at']);
        });

        Schema::table('retraites', function (Blueprint $table) {
            $table->dropColumn(['verification_code', 'verification_code_expires_at', 'phone_verified_at']);
        });
    }
};