<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            if (!Schema::hasColumn('artists', 'phone')) {
                $table->string('phone', 50)->nullable()->after('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            if (Schema::hasColumn('artists', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
