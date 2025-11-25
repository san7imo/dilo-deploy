<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cover_url')->nullable(); // portada (usada en carruseles)
            $table->date('release_date')->nullable();

            // Tipo: single, ep, album, mixtape, live, compilation...
            $table->string('type')->default('single');

            // Plataforma individuales (opcional), puedes usar JSON platforms si prefieres
            $table->string('spotify_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('apple_music_url')->nullable();
            $table->string('deezer_url')->nullable();
            $table->string('amazon_music_url')->nullable();
            $table->string('soundcloud_url')->nullable();
            $table->string('tidal_url')->nullable();

            // Campos extra
            $table->text('description')->nullable();
            $table->json('extra')->nullable(); // reservado para metadatos futuros

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
