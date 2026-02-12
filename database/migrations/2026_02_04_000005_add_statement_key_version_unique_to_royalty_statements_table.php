<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('royalty_statements', function (Blueprint $table) {
            $table->index('statement_key');
            $table->unique(['statement_key', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('royalty_statements', function (Blueprint $table) {
            $table->dropUnique(['statement_key', 'version']);
            $table->dropIndex(['statement_key']);
        });
    }
};
