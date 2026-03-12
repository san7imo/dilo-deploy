<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'google_maps_url')) {
                $table->string('google_maps_url', 500)->nullable()->after('organizer_email');
            }

            if (!Schema::hasColumn('events', 'google_maps_place_id')) {
                $table->string('google_maps_place_id', 120)->nullable()->after('google_maps_url');
            }

            if (!Schema::hasColumn('events', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_place_id');
            }

            if (!Schema::hasColumn('events', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = [
                'google_maps_url',
                'google_maps_place_id',
                'latitude',
                'longitude',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn(string $column): bool => Schema::hasColumn('events', $column)
            ));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
