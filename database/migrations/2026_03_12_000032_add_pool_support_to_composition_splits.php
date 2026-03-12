<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_split_participants')) {
            Schema::table('composition_split_participants', function (Blueprint $table) {
                if (!Schema::hasColumn('composition_split_participants', 'composition_split_set_id')) {
                    $table->unsignedBigInteger('composition_split_set_id')
                        ->nullable()
                        ->after('id');
                }

                if (!Schema::hasColumn('composition_split_participants', 'share_pool')) {
                    $table->string('share_pool', 50)
                        ->default('writer')
                        ->after('role');
                }
            });

            Schema::table('composition_split_participants', function (Blueprint $table) {
                $table->index('composition_split_set_id', 'csp_css_idx');
                $table->index(['composition_split_set_id', 'share_pool'], 'csp_css_pool_idx');
                $table->foreign('composition_split_set_id', 'csp_css_fk')
                    ->references('id')
                    ->on('composition_split_sets')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('royalty_allocations') && !Schema::hasColumn('royalty_allocations', 'composition_split_set_id')) {
            Schema::table('royalty_allocations', function (Blueprint $table) {
                $table->foreignId('composition_split_set_id')
                    ->nullable()
                    ->after('composition_split_agreement_id')
                    ->constrained('composition_split_sets', 'id', 'royalty_allocations_css_fk')
                    ->nullOnDelete();
            });
        }

        if (
            Schema::hasTable('composition_split_sets')
            && Schema::hasTable('composition_split_agreements')
            && Schema::hasTable('composition_split_participants')
            && !DB::table('composition_split_sets')->exists()
        ) {
            $agreements = DB::table('composition_split_agreements')
                ->orderBy('composition_id')
                ->orderBy('effective_from')
                ->orderBy('created_at')
                ->get();

            $versionsByComposition = [];

            foreach ($agreements as $agreement) {
                $compositionId = (int) $agreement->composition_id;
                $versionsByComposition[$compositionId] = ($versionsByComposition[$compositionId] ?? 0) + 1;

                DB::table('composition_split_sets')->insert([
                    'id' => $agreement->id,
                    'composition_id' => $compositionId,
                    'version' => $versionsByComposition[$compositionId],
                    'status' => $agreement->status ?: 'active',
                    'effective_from' => $agreement->effective_from,
                    'effective_to' => $agreement->effective_to,
                    'contract_path' => $agreement->contract_path,
                    'contract_original_filename' => $agreement->contract_original_filename,
                    'contract_hash' => $agreement->contract_hash,
                    'created_by' => $agreement->created_by,
                    'created_at' => $agreement->created_at,
                    'updated_at' => $agreement->updated_at,
                    'deleted_at' => $agreement->deleted_at,
                ]);
            }
        }

        if (Schema::hasTable('composition_split_participants') && Schema::hasColumn('composition_split_participants', 'composition_split_set_id')) {
            DB::table('composition_split_participants')
                ->whereNull('composition_split_set_id')
                ->whereNotNull('composition_split_agreement_id')
                ->update([
                    'composition_split_set_id' => DB::raw('composition_split_agreement_id'),
                ]);

            DB::statement("
                UPDATE composition_split_participants
                SET share_pool = CASE
                    WHEN LOWER(COALESCE(role, '')) = 'publisher' THEN 'publisher'
                    WHEN LOWER(COALESCE(role, '')) IN ('mechanical_payee', 'mechanical') THEN 'mechanical_payee'
                    ELSE 'writer'
                END
                WHERE share_pool IS NULL OR share_pool = ''
            ");
        }

        if (
            Schema::hasTable('royalty_allocations')
            && Schema::hasColumn('royalty_allocations', 'composition_split_set_id')
            && Schema::hasColumn('royalty_allocations', 'composition_split_agreement_id')
        ) {
            DB::table('royalty_allocations')
                ->whereNull('composition_split_set_id')
                ->whereNotNull('composition_split_agreement_id')
                ->update([
                    'composition_split_set_id' => DB::raw('composition_split_agreement_id'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('royalty_allocations') && Schema::hasColumn('royalty_allocations', 'composition_split_set_id')) {
            Schema::table('royalty_allocations', function (Blueprint $table) {
                $table->dropForeign('royalty_allocations_css_fk');
                $table->dropColumn('composition_split_set_id');
            });
        }

        if (Schema::hasTable('composition_split_participants')) {
            Schema::table('composition_split_participants', function (Blueprint $table) {
                if (Schema::hasColumn('composition_split_participants', 'composition_split_set_id')) {
                    $table->dropForeign('csp_css_fk');
                    $table->dropIndex('csp_css_pool_idx');
                    $table->dropIndex('csp_css_idx');
                    $table->dropColumn('composition_split_set_id');
                }

                if (Schema::hasColumn('composition_split_participants', 'share_pool')) {
                    $table->dropColumn('share_pool');
                }
            });
        }
    }
};
