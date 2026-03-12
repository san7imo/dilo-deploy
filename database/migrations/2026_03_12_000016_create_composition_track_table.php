<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_track')) {
            return;
        }

        Schema::create('composition_track', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained('compositions')->cascadeOnDelete();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['composition_id', 'track_id']);
            $table->index(['track_id', 'composition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_track');
    }
};
