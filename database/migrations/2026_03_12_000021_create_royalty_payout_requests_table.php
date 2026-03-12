<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('requester_artist_id')
                ->nullable()
                ->constrained('artists')
                ->nullOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->decimal('requested_amount_usd', 18, 6)->default(0);
            $table->decimal('minimum_threshold_usd', 18, 6)->default(50);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_at'], 'royalty_payout_requests_status_requested_at_idx');
            $table->index(['requester_user_id', 'status'], 'royalty_payout_requests_user_status_idx');
            $table->index(['requester_artist_id', 'status'], 'royalty_payout_requests_artist_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_payout_requests');
    }
};
