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
        // 1. Create user_gear_media table
        Schema::create('user_gear_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('label')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->integer('size'); // bytes
            $table->timestamps();
        });

        // 2. Add fields to user_profiles
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('draft_attack')->nullable();
            $table->integer('draft_awakening_attack')->nullable();
            $table->integer('draft_defense')->nullable();
        });

        // 3. Add fields to guild_members
        Schema::table('guild_members', function (Blueprint $table) {
            $table->string('verification_status')->default('pending');
            $table->bigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guild_members', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verification_status', 'verified_by', 'verified_at']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['draft_attack', 'draft_awakening_attack', 'draft_defense']);
        });

        Schema::dropIfExists('user_gear_media');
    }
};
