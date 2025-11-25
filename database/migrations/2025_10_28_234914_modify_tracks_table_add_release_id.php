<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            // Agregamos release_id nullable (relación con releases)
            if (!Schema::hasColumn('tracks', 'release_id')) {
                $table->foreignId('release_id')
                      ->nullable()
                      ->constrained('releases')
                      ->nullOnDelete()
                      ->after('id');
            }

            // Si el campo is_release existe, lo eliminamos (ya no se usa)
            if (Schema::hasColumn('tracks', 'is_release')) {
                $table->dropColumn('is_release');
            }

            // Aseguramos que exista cover_url para cada track
            if (!Schema::hasColumn('tracks', 'cover_url')) {
                $table->string('cover_url')->nullable()->after('duration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (Schema::hasColumn('tracks', 'release_id')) {
                $table->dropForeign(['release_id']);
                $table->dropColumn('release_id');
            }

            if (Schema::hasColumn('tracks', 'cover_url')) {
                $table->dropColumn('cover_url');
            }

            // (Opcional) restaurar is_release si existía antes
            // $table->boolean('is_release')->default(false)->after('duration');
        });
    }
};
