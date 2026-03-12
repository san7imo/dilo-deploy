<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_allocations')) {
            return;
        }

        Schema::create('composition_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('composition_royalty_statement_id');
            $table->unsignedBigInteger('composition_royalty_line_id');
            $table->unsignedBigInteger('composition_id')->nullable();
            $table->unsignedBigInteger('composition_split_set_id')->nullable();
            $table->unsignedBigInteger('composition_split_participant_id')->nullable();
            $table->string('share_pool', 50);
            $table->string('line_type', 30);
            $table->unsignedBigInteger('party_user_id')->nullable();
            $table->unsignedBigInteger('party_artist_id')->nullable();
            $table->string('party_email')->nullable();
            $table->string('role')->nullable();
            $table->date('activity_month_date')->nullable();
            $table->decimal('split_percentage', 8, 4);
            $table->decimal('gross_amount_usd', 18, 6);
            $table->decimal('allocated_amount_usd', 18, 6);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('accrued');
            $table->timestamps();

            $table->unique(
                ['composition_royalty_line_id', 'composition_split_participant_id', 'share_pool'],
                'composition_allocations_line_participant_pool_uk'
            );
            $table->index(['composition_royalty_statement_id', 'status'], 'composition_allocations_statement_status_idx');
            $table->index(['composition_id', 'line_type'], 'composition_allocations_composition_type_idx');
            $table->index(['party_user_id', 'party_artist_id'], 'composition_allocations_party_idx');

            $table->foreign('composition_royalty_statement_id', 'ca_crs_fk')
                ->references('id')
                ->on('composition_royalty_statements')
                ->cascadeOnDelete();

            $table->foreign('composition_royalty_line_id', 'ca_crl_fk')
                ->references('id')
                ->on('composition_royalty_lines')
                ->cascadeOnDelete();

            $table->foreign('composition_id', 'ca_composition_fk')
                ->references('id')
                ->on('compositions')
                ->nullOnDelete();

            $table->foreign('composition_split_set_id', 'ca_css_fk')
                ->references('id')
                ->on('composition_split_sets')
                ->nullOnDelete();

            $table->foreign('composition_split_participant_id', 'ca_csp_fk')
                ->references('id')
                ->on('composition_split_participants')
                ->nullOnDelete();

            $table->foreign('party_user_id', 'ca_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('party_artist_id', 'ca_artist_fk')
                ->references('id')
                ->on('artists')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_allocations');
    }
};
