<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_discord_id_unique');
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_telegram_id_unique');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['discord_id', 'telegram_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_id')->nullable()->unique()->after('email');
            $table->string('telegram_id')->nullable()->unique()->after('discord_id');
        });
    }
};
