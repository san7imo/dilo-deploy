<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_allocation_recalculations')) {
            return;
        }

        Schema::create('composition_allocation_recalculations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('composition_royalty_statement_id');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->string('trigger_source')->default('system');
            $table->string('reason')->nullable();
            $table->unsignedInteger('lines_total')->default(0);
            $table->unsignedInteger('lines_matched')->default(0);
            $table->unsignedInteger('allocations_count')->default(0);
            $table->decimal('allocations_total_usd', 18, 6)->default(0);
            $table->json('warnings')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['composition_royalty_statement_id', 'created_at'], 'car_statement_created_idx');
            $table->index(['trigger_source', 'created_at'], 'car_source_created_idx');

            $table->foreign('composition_royalty_statement_id', 'car_statement_fk')
                ->references('id')
                ->on('composition_royalty_statements')
                ->cascadeOnDelete();

            $table->foreign('triggered_by_user_id', 'car_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_allocation_recalculations');
    }
};
