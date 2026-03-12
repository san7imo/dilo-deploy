<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_allocation_recalculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_statement_id')
                ->constrained('royalty_statements')
                ->cascadeOnDelete();
            $table->foreignId('triggered_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('trigger_source', 64)->default('system');
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('lines_total')->default(0);
            $table->unsignedBigInteger('lines_matched')->default(0);
            $table->unsignedBigInteger('master_allocations_count')->default(0);
            $table->unsignedBigInteger('composition_allocations_count')->default(0);
            $table->json('warnings')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('royalty_statement_id');
            $table->index('trigger_source');
            $table->index('triggered_by_user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_allocation_recalculations');
    }
};

