<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('royalty_statement_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('royalty_statement_lines', 'match_status')) {
                $table->string('match_status', 20)
                    ->default('unmatched')
                    ->after('track_id');
                $table->index('match_status');
            }

            if (!Schema::hasColumn('royalty_statement_lines', 'match_meta')) {
                $table->json('match_meta')
                    ->nullable()
                    ->after('raw');
            }
        });

        if (Schema::hasColumn('royalty_statement_lines', 'match_status')) {
            DB::statement("
                UPDATE royalty_statement_lines rsl
                LEFT JOIN royalty_statements rs ON rs.id = rsl.royalty_statement_id
                SET rsl.match_status = CASE
                    WHEN rs.is_reference_only = 1 THEN 'reference_only'
                    WHEN rsl.track_id IS NOT NULL THEN 'matched'
                    ELSE 'unmatched'
                END
            ");
        }
    }

    public function down(): void
    {
        Schema::table('royalty_statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('royalty_statement_lines', 'match_status')) {
                $table->dropIndex(['match_status']);
                $table->dropColumn('match_status');
            }

            if (Schema::hasColumn('royalty_statement_lines', 'match_meta')) {
                $table->dropColumn('match_meta');
            }
        });
    }
};

