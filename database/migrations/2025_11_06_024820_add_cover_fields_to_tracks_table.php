<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('tracks', 'cover_url')) {
                $table->string('cover_url')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('tracks', 'cover_id')) {
                $table->string('cover_id')->nullable()->after('cover_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['cover_url', 'cover_id']);
        });
    }
};
