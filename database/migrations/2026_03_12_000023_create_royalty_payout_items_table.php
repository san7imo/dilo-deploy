<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_payout_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('royalty_payout_payment_id');
            $table->unsignedBigInteger('royalty_allocation_id');
            $table->decimal('amount_usd', 18, 6)->default(0);
            $table->timestamps();

            $table->index('royalty_payout_payment_id', 'rpi_payment_idx');
            $table->index('royalty_allocation_id', 'rpi_allocation_idx');

            $table->foreign('royalty_payout_payment_id', 'rpi_payment_fk')
                ->references('id')
                ->on('royalty_payout_payments')
                ->cascadeOnDelete();

            $table->foreign('royalty_allocation_id', 'rpi_allocation_fk')
                ->references('id')
                ->on('royalty_allocations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_payout_items');
    }
};
