<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas de negocio con modelo Eloquent propio.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'users',
        'artists',
        'collaborators',
        'events',
        'event_payments',
        'event_expenses',
        'event_personal_expenses',
        'genres',
        'releases',
        'tracks',
        'royalty_statements',
        'royalty_statement_lines',
        'track_split_agreements',
        'track_split_participants',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
