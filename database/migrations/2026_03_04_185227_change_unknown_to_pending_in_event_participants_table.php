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
        // Сначала обновим существующие данные
        DB::table('event_participants')
            ->where('status', 'unknown')
            ->update(['status' => 'pending']);

        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->enum('status', ['confirmed', 'declined', 'unknown'])->default('unknown')->change();
        });

        DB::table('event_participants')
            ->where('status', 'pending')
            ->update(['status' => 'unknown']);
    }
};
