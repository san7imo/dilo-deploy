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
        Schema::create('artist_event_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
            ->constrained('events')
            ->onDelete('cascade');

            $table->foreignId('artist_id')
            ->constrained('users')
            ->onDelete('cascade');

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category');
            $table->date('expense_date');
            $table->decimal('amount_original', 15, 2);
            $table->string('currency', 3);
            $table->decimal('exchange_rate_to_base', 10, 4)->default(1);
            $table->decimal('amount_base', 15, 2);
            $table->string('receipt_url')->nullable();
            $table->string('receipt_id')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by_user_id')
            ->constrained('users');

            $table->timestamp('approved_at')
            ->nullable();

            $table->timestamps();

            $table->index(['event_id', 'artist_id']);
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_event_expenses');
    }
};
