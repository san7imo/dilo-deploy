<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'identification_type')) {
                $table->string('identification_type', 40)
                    ->nullable()
                    ->after('identification_number');
            }

            if (!Schema::hasColumn('users', 'additional_information')) {
                $table->text('additional_information')
                    ->nullable()
                    ->after('identification_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'additional_information')) {
                $table->dropColumn('additional_information');
            }

            if (Schema::hasColumn('users', 'identification_type')) {
                $table->dropColumn('identification_type');
            }
        });
    }
};
