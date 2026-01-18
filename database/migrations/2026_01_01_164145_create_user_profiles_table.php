<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('family_name')->index();
            $table->string('char_class')->nullable();
            $table->integer('gear_score')->default(0);
            $table->integer('attack')->default(0);
            $table->integer('awakening_attack')->default(0);
            $table->integer('defense')->default(0);
            $table->integer('level')->default(0);

            $table->timestamps();
        });
    }
};
