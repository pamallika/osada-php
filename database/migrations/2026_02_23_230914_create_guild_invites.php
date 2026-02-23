<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique(); // invite hash
            $table->integer('max_uses')->nullable(); // limit (null = unlimit)
            $table->integer('uses')->default(0); // count used
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_invites');
    }
};
