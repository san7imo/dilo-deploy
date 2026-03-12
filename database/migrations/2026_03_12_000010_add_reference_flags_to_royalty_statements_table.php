<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('royalty_statements', function (Blueprint $table) {
            if (!Schema::hasColumn('royalty_statements', 'is_reference_only')) {
                $table->boolean('is_reference_only')
                    ->default(false)
                    ->after('is_current');
                $table->index('is_reference_only');
            }

            if (!Schema::hasColumn('royalty_statements', 'duplicate_of_statement_id')) {
                $table->foreignId('duplicate_of_statement_id')
                    ->nullable()
                    ->after('is_reference_only')
                    ->constrained('royalty_statements')
                    ->nullOnDelete();
                $table->index('duplicate_of_statement_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('royalty_statements', function (Blueprint $table) {
            if (Schema::hasColumn('royalty_statements', 'duplicate_of_statement_id')) {
                $table->dropConstrainedForeignId('duplicate_of_statement_id');
            }

            if (Schema::hasColumn('royalty_statements', 'is_reference_only')) {
                $table->dropIndex(['is_reference_only']);
                $table->dropColumn('is_reference_only');
            }
        });
    }
};
