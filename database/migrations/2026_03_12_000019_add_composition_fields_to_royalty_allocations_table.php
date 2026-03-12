<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('royalty_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('royalty_allocations', 'right_type')) {
                $table->string('right_type', 20)
                    ->default('master')
                    ->after('track_id');
                $table->index('right_type', 'royalty_allocations_right_type_idx');
            }

            if (!Schema::hasColumn('royalty_allocations', 'composition_id')) {
                $table->foreignId('composition_id')
                    ->nullable()
                    ->after('right_type')
                    ->constrained('compositions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('royalty_allocations', 'composition_split_agreement_id')) {
                $table->foreignId('composition_split_agreement_id')
                    ->nullable()
                    ->after('track_split_agreement_id')
                    ->constrained('composition_split_agreements')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('royalty_allocations', 'composition_split_participant_id')) {
                $table->foreignId('composition_split_participant_id')
                    ->nullable()
                    ->after('track_split_participant_id')
                    ->constrained('composition_split_participants')
                    ->nullOnDelete();
            }
        });

        $table = 'royalty_allocations';
        $index = 'royalty_allocations_line_composition_participant_unique';
        $indexExists = collect(Schema::getConnection()->select("SHOW INDEX FROM {$table}")) // phpcs:ignore
            ->pluck('Key_name')
            ->contains($index);

        if (!$indexExists) {
            Schema::table('royalty_allocations', function (Blueprint $table) {
                $table->unique(
                    ['royalty_statement_line_id', 'composition_split_participant_id'],
                    'royalty_allocations_line_composition_participant_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('royalty_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('royalty_allocations', 'composition_split_participant_id')) {
                $table->dropUnique('royalty_allocations_line_composition_participant_unique');
                $table->dropConstrainedForeignId('composition_split_participant_id');
            }

            if (Schema::hasColumn('royalty_allocations', 'composition_split_agreement_id')) {
                $table->dropConstrainedForeignId('composition_split_agreement_id');
            }

            if (Schema::hasColumn('royalty_allocations', 'composition_id')) {
                $table->dropConstrainedForeignId('composition_id');
            }

            if (Schema::hasColumn('royalty_allocations', 'right_type')) {
                $table->dropIndex('royalty_allocations_right_type_idx');
                $table->dropColumn('right_type');
            }
        });
    }
};
