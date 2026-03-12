<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_royalty_lines')) {
            return;
        }

        Schema::create('composition_royalty_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('composition_royalty_statement_id');
            $table->unsignedBigInteger('composition_id')->nullable();
            $table->string('line_type', 30);
            $table->string('source_line_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('composition_iswc')->nullable();
            $table->string('composition_title')->nullable();
            $table->string('source_name')->nullable();
            $table->string('territory_code', 10)->nullable();
            $table->string('activity_period_text')->nullable();
            $table->date('activity_month_date')->nullable();
            $table->unsignedBigInteger('units')->default(0);
            $table->decimal('amount_usd', 18, 6);
            $table->string('currency', 3)->default('USD');
            $table->string('line_hash', 64);
            $table->string('match_status', 20)->default('unmatched');
            $table->json('match_meta')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['composition_royalty_statement_id', 'line_hash'],
                'composition_royalty_lines_statement_line_hash_uk'
            );
            $table->index(['composition_id', 'line_type'], 'composition_royalty_lines_composition_type_idx');
            $table->index(['activity_month_date', 'line_type'], 'composition_royalty_lines_activity_type_idx');
            $table->index(['match_status'], 'composition_royalty_lines_match_status_idx');

            $table->foreign('composition_royalty_statement_id', 'crl_crs_fk')
                ->references('id')
                ->on('composition_royalty_statements')
                ->cascadeOnDelete();

            $table->foreign('composition_id', 'crl_composition_fk')
                ->references('id')
                ->on('compositions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_royalty_lines');
    }
};
