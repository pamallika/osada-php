<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('discord_id')->unique();
            $table->string('username');
            $table->string('global_name')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }
};
