<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // 'discord' || 'telegram || ...'
            $table->string('platform_id')->nullable(); // ID сервера в Discord или ID чата в Telegram
            $table->string('announcement_channel_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['guild_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_integrations');
    }
};
