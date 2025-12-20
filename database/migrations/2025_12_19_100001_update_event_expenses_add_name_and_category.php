<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_expenses', function (Blueprint $table) {
            // Agregar columna 'name' después de description
            $table->string('name')->nullable()->after('description');

            // Agregar columna 'category'
            $table->string('category')->nullable()->after('name');

            // Índice para category
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('event_expenses', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['name', 'category']);
        });
    }
};
