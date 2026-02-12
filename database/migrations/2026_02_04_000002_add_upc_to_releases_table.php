<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            if (!Schema::hasColumn('releases', 'upc')) {
                $table->string('upc', 32)->nullable()->after('title');
                $table->index('upc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            if (Schema::hasColumn('releases', 'upc')) {
                $table->dropIndex(['upc']);
                $table->dropColumn('upc');
            }
        });
    }
};
