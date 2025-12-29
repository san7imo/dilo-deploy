<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('expense_date')->nullable();
            $table->string('description')->nullable();

            // Monto original
            $table->decimal('amount_original', 14, 2);
            $table->string('currency', 3);

            // Monto normalizado a USD
            $table->decimal('exchange_rate_to_base', 12, 6)->default(1);
            $table->decimal('amount_base', 14, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expenses');
    }
};
