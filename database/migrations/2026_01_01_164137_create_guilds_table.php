<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->id();
            $table->string('discord_id')->unique();
            $table->string('name');

            // Настройки каналов
            $table->string('public_channel_id')->nullable();

            // Роли офицеров храним в JSON массиве
            $table->json('officer_role_ids')->nullable();

            $table->timestamps();
        });
    }
};
