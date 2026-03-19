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
        Schema::table('events', function (Blueprint $table) {
            // В 2026_01_01_164157_create_events_table.php уже есть status enum.
            // Но задача требует 'draft', 'published', 'archived'. 
            // Текущий: 'draft', 'published', 'completed', 'cancelled'.
            // Оставим как есть или обновим до string если нужно гибче. 
            // В задаче сказано string: draft, published, archived.
            $table->string('status')->default('draft')->change();
        });

        Schema::table('event_squads', function (Blueprint $table) {
            $table->boolean('is_system')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'completed', 'cancelled'])->default('draft')->change();
        });

        Schema::table('event_squads', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
