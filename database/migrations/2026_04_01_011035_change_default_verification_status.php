<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change default value for verification_status in guild_members
        Schema::table('guild_members', function (Blueprint $table) {
            $table->string('verification_status')->default('incomplete')->change();
        });

        // 2. Clear ghost pending status
        DB::table('guild_members')
            ->where('verification_status', 'pending')
            ->update(['verification_status' => 'incomplete']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guild_members', function (Blueprint $table) {
            $table->string('verification_status')->default('pending')->change();
        });
    }
};
