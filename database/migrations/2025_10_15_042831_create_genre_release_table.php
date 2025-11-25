<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genre_release', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->foreignId('release_id')->constrained('releases')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['genre_id', 'release_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_release');
    }
};
