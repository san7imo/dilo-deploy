<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateKeys = DB::table('royalty_statements')
            ->whereNotNull('statement_key')
            ->where('statement_key', '!=', '')
            ->whereNull('deleted_at')
            ->where('is_current', true)
            ->groupBy('statement_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('statement_key');

        $now = now();

        foreach ($duplicateKeys as $statementKey) {
            $winnerId = DB::table('royalty_statements')
                ->where('statement_key', $statementKey)
                ->whereNull('deleted_at')
                ->where('is_current', true)
                ->orderByDesc('version')
                ->orderByDesc('id')
                ->value('id');

            if (!$winnerId) {
                continue;
            }

            DB::table('royalty_statements')
                ->where('statement_key', $statementKey)
                ->whereNull('deleted_at')
                ->where('is_current', true)
                ->where('id', '!=', $winnerId)
                ->update([
                    'is_current' => false,
                    'updated_at' => $now,
                ]);
        }

        Schema::table('royalty_statements', function (Blueprint $table) {
            if (!Schema::hasColumn('royalty_statements', 'current_statement_key')) {
                $table->string('current_statement_key')
                    ->nullable()
                    ->storedAs('IF(is_current = 1 AND deleted_at IS NULL, statement_key, NULL)')
                    ->after('statement_key');
            }
        });

        Schema::table('royalty_statements', function (Blueprint $table) {
            $table->unique('current_statement_key', 'royalty_statements_current_statement_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('royalty_statements', function (Blueprint $table) {
            $table->dropUnique('royalty_statements_current_statement_key_unique');

            if (Schema::hasColumn('royalty_statements', 'current_statement_key')) {
                $table->dropColumn('current_statement_key');
            }
        });
    }
};
