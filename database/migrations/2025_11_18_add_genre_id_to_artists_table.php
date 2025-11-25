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
        Schema::table('artists', function (Blueprint $table) {
            // Agregar columna genre_id si no existe
            if (!Schema::hasColumn('artists', 'genre_id')) {
                $table->foreignId('genre_id')
                    ->nullable()
                    ->constrained('genres')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            if (Schema::hasColumn('artists', 'genre_id')) {
                $table->dropForeignIdFor('Genre');
                $table->dropColumn('genre_id');
            }
        });
    }
};
