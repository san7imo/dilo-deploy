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
            $table->foreignId('main_artist_id')
                ->nullable()
                ->after('id')
                ->constrained('artists')
                ->nullOnDelete();

            $table->index('main_artist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['main_artist_id']);
            $table->dropColumn('main_artist_id');
        });
    }
};
