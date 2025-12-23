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
        Schema::create('event_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('payment_date');

            // Monto original
            $table->decimal('amount_original', 14, 2);
            $table->string('currency', 3); // EUR, USD, COP, MXN, etc

            // Normalización a moneda base (EUR)
            $table->decimal('exchange_rate_to_base', 12, 6);
            $table->decimal('amount_base', 14, 2); // EUR

            $table->string('payment_method')->nullable(); // transfer, cash, paypal, etc
            $table->boolean('is_advance')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_payments');
    }
};
