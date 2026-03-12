<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stage_name')) {
                $table->string('stage_name')->nullable()->after('name');
            }

            if (!Schema::hasColumn('users', 'identification_number')) {
                $table->string('identification_number', 80)->nullable()->after('phone');
                $table->unique('identification_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'identification_number')) {
                $table->dropUnique(['identification_number']);
                $table->dropColumn('identification_number');
            }

            if (Schema::hasColumn('users', 'stage_name')) {
                $table->dropColumn('stage_name');
            }
        });
    }
};
