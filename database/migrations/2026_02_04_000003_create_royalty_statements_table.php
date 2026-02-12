<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statements', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('label')->nullable();
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
            $table->unsignedBigInteger('total_units')->default(0);
            $table->decimal('total_net_usd', 18, 6)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['provider', 'file_hash']);
            $table->index('provider');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_statements');
    }
};
