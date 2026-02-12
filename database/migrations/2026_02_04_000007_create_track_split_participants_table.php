<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_split_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_split_agreement_id')
                ->constrained('track_split_agreements')
                ->cascadeOnDelete();
            $table->foreignId('artist_id')
                ->nullable()
                ->constrained('artists')
                ->nullOnDelete();
            $table->string('payee_email')->nullable();
            $table->string('name')->nullable();
            $table->string('role', 50);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();

            $table->index('track_split_agreement_id');
            $table->index('artist_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_split_participants');
    }
};
