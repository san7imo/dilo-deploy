<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Solo agregar si no existen
            if (!Schema::hasColumn('events', 'poster_url')) {
                $table->string('poster_url', 255)->nullable()->after('event_date');
            }

            if (!Schema::hasColumn('events', 'poster_id')) {
                $table->string('poster_id', 255)->nullable()->after('poster_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['poster_url', 'poster_id']);
        });
    }
};
