<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_statement_id')
                ->constrained('royalty_statements')
                ->cascadeOnDelete();
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('tracks')
                ->nullOnDelete();
            $table->string('isrc', 32)->nullable();
            $table->string('upc', 32)->nullable();
            $table->string('track_title')->nullable();
            $table->string('channel')->nullable();
            $table->string('country', 64)->nullable();
            $table->string('activity_period_text')->nullable();
            $table->date('activity_month_date')->nullable();
            $table->unsignedBigInteger('units')->default(0);
            $table->decimal('net_total_usd', 18, 6)->default(0);
            $table->string('line_hash', 64)->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index('track_id');
            $table->index('isrc');
            $table->index('activity_month_date');
            $table->unique(['royalty_statement_id', 'line_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_statement_lines');
    }
};
