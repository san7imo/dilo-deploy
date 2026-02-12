<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            if (!Schema::hasColumn('collaborators', 'phone')) {
                $table->string('phone', 50)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            if (Schema::hasColumn('collaborators', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
