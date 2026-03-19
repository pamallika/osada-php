<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('status_new')->default('unknown');
        });

        DB::statement('UPDATE event_participants SET status_new = status');

        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('event_participants', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('status_new')->default('unknown');
        });

        DB::statement('UPDATE event_participants SET status_new = status');

        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('event_participants', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }
};
