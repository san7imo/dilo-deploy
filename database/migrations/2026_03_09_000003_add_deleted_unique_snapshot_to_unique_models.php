<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas con columnas únicas que deben poder reciclarse tras soft delete.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'users',
        'artists',
        'genres',
        'releases',
        'events',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'deleted_unique_snapshot')) {
                    $table->json('deleted_unique_snapshot')->nullable()->after('deleted_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'deleted_unique_snapshot')) {
                    $table->dropColumn('deleted_unique_snapshot');
                }
            });
        }
    }
};
