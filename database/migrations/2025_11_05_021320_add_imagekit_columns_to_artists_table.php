<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            // Verifica primero que no existan antes de crear
            if (!Schema::hasColumn('artists', 'banner_home_url')) {
                $table->string('banner_home_url')->nullable()->after('country');
            }
            if (!Schema::hasColumn('artists', 'banner_home_id')) {
                $table->string('banner_home_id')->nullable()->after('banner_home_url');
            }

            if (!Schema::hasColumn('artists', 'banner_artist_url')) {
                $table->string('banner_artist_url')->nullable()->after('banner_home_id');
            }
            if (!Schema::hasColumn('artists', 'banner_artist_id')) {
                $table->string('banner_artist_id')->nullable()->after('banner_artist_url');
            }

            if (!Schema::hasColumn('artists', 'carousel_home_url')) {
                $table->string('carousel_home_url')->nullable()->after('banner_artist_id');
            }
            if (!Schema::hasColumn('artists', 'carousel_home_id')) {
                $table->string('carousel_home_id')->nullable()->after('carousel_home_url');
            }

            if (!Schema::hasColumn('artists', 'carousel_discography_url')) {
                $table->string('carousel_discography_url')->nullable()->after('carousel_home_id');
            }
            if (!Schema::hasColumn('artists', 'carousel_discography_id')) {
                $table->string('carousel_discography_id')->nullable()->after('carousel_discography_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn([
                'banner_home_url',
                'banner_home_id',
                'banner_artist_url',
                'banner_artist_id',
                'carousel_home_url',
                'carousel_home_id',
                'carousel_discography_url',
                'carousel_discography_id',
            ]);
        });
    }
};
