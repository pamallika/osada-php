<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');

            $table->string('region');
            $table->dateTime('start_at');
            $table->integer('total_slots');

            $table->boolean('is_free_registration')->default(false);

            $table->enum('status', ['draft', 'published', 'completed', 'cancelled'])->default('draft');

            $table->string('discord_message_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
