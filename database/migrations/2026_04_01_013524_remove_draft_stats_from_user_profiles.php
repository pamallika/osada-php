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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['draft_attack', 'draft_awakening_attack', 'draft_defense']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('draft_attack')->nullable();
            $table->integer('draft_awakening_attack')->nullable();
            $table->integer('draft_defense')->nullable();
        });
    }
};
