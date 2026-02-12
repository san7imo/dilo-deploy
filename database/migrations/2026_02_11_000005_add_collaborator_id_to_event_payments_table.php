<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('event_payments', 'collaborator_id')) {
                $table->foreignId('collaborator_id')
                    ->nullable()
                    ->constrained('collaborators')
                    ->nullOnDelete()
                    ->after('event_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_payments', function (Blueprint $table) {
            if (Schema::hasColumn('event_payments', 'collaborator_id')) {
                $table->dropConstrainedForeignId('collaborator_id');
            }
        });
    }
};
