<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_personal_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('artist_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('expense_date')->nullable();
            $table->string('expense_type', 100)->nullable();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('payment_method', 100);
            $table->string('recipient', 255)->nullable();

            $table->decimal('amount_original', 14, 2);
            $table->string('currency', 3);
            $table->decimal('exchange_rate_to_base', 12, 6)->default(1);
            $table->decimal('amount_base', 14, 2);

            $table->timestamps();

            $table->index(['event_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_personal_expenses');
    }
};
