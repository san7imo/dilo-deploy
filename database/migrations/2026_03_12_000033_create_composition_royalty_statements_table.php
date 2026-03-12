<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_royalty_statements')) {
            return;
        }

        Schema::create('composition_royalty_statements', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('manual_dilo');
            $table->string('source_name')->nullable();
            $table->string('reporting_period')->nullable();
            $table->date('reporting_month_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_hash', 64);
            $table->string('statement_key')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('status', 20)->default('uploaded');
            $table->unsignedInteger('total_lines')->default(0);
            $table->unsignedBigInteger('total_units')->default(0);
            $table->decimal('total_amount_usd', 18, 6)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'file_hash'], 'composition_royalty_statements_provider_file_hash_uk');
            $table->unique(['statement_key', 'version'], 'composition_royalty_statements_statement_key_version_uk');
            $table->index(['statement_key', 'is_current'], 'composition_royalty_statements_statement_key_current_idx');
            $table->index(['status', 'reporting_month_date'], 'composition_royalty_statements_status_reporting_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_royalty_statements');
    }
};

