<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_statement_id')
                ->constrained('royalty_statements')
                ->cascadeOnDelete();
            $table->foreignId('royalty_statement_line_id')
                ->constrained('royalty_statement_lines')
                ->cascadeOnDelete();
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('tracks')
                ->nullOnDelete();
            $table->foreignId('track_split_agreement_id')
                ->nullable()
                ->constrained('track_split_agreements')
                ->nullOnDelete();
            $table->foreignId('track_split_participant_id')
                ->nullable()
                ->constrained('track_split_participants')
                ->nullOnDelete();
            $table->foreignId('party_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('party_artist_id')
                ->nullable()
                ->constrained('artists')
                ->nullOnDelete();
            $table->string('party_email')->nullable();
            $table->string('role', 50)->nullable();
            $table->date('activity_month_date')->nullable();
            $table->decimal('split_percentage', 8, 4);
            $table->decimal('gross_amount_usd', 18, 6)->default(0);
            $table->decimal('allocated_amount_usd', 18, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('accrued');
            $table->timestamps();

            $table->unique(
                ['royalty_statement_line_id', 'track_split_participant_id'],
                'royalty_allocations_line_participant_unique'
            );
            $table->index(['royalty_statement_id', 'status'], 'royalty_allocations_statement_status_idx');
            $table->index(['track_id', 'activity_month_date'], 'royalty_allocations_track_activity_idx');
            $table->index(['party_user_id', 'activity_month_date'], 'royalty_allocations_party_user_activity_idx');
            $table->index(['party_artist_id', 'activity_month_date'], 'royalty_allocations_party_artist_activity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_allocations');
    }
};
