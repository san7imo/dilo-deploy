<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $columns = [
                'spotify_url',
                'youtube_url',
                'apple_music_url',
                'deezer_url',
                'amazon_music_url',
                'soundcloud_url',
                'tidal_url',
            ];

            foreach ($columns as $column) {
                if (!Schema::hasColumn('tracks', $column)) {
                    $table->string($column)->nullable()->after('preview_url');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn([
                'spotify_url',
                'youtube_url',
                'apple_music_url',
                'deezer_url',
                'amazon_music_url',
                'soundcloud_url',
                'tidal_url',
            ]);
        });
    }
};
