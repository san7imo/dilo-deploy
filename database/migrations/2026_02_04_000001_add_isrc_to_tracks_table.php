<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('tracks', 'isrc')) {
                $table->string('isrc', 32)->nullable()->after('title');
                $table->index('isrc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (Schema::hasColumn('tracks', 'isrc')) {
                $table->dropIndex(['isrc']);
                $table->dropColumn('isrc');
            }
        });
    }
};
