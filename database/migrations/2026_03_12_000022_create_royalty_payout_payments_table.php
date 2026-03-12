<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_payout_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_payout_request_id')
                ->constrained('royalty_payout_requests')
                ->cascadeOnDelete();
            $table->decimal('amount_usd', 18, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->date('paid_at');
            $table->text('description')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['royalty_payout_request_id', 'paid_at'], 'royalty_payout_payments_request_paid_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_payout_payments');
    }
};
