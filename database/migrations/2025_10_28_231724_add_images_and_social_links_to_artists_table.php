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
            // Imágenes principales y de carruseles
            $table->string('banner_home_url')->nullable()->after('bio');             // Banner principal en el home
            $table->string('banner_artist_url')->nullable()->after('banner_home_url'); // Banner en la página del artista
            $table->string('carousel_home_url')->nullable()->after('banner_artist_url'); // Imagen para carrusel del home
            $table->string('carousel_discography_url')->nullable()->after('carousel_home_url'); // Imagen para carrusel de discografía

            // Redes sociales dinámicas (JSON)
            $table->json('social_links')->nullable()->after('tiktok_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn([
                'banner_home_url',
                'banner_artist_url',
                'carousel_home_url',
                'carousel_discography_url',
                'social_links',
            ]);
        });
    }
};
