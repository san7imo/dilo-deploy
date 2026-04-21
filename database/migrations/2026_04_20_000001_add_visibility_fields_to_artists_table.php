<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            if (!Schema::hasColumn('artists', 'artist_origin')) {
                $table->string('artist_origin', 20)
                    ->default('internal')
                    ->after('user_id');
            }

            if (!Schema::hasColumn('artists', 'has_public_profile')) {
                $table->boolean('has_public_profile')
                    ->default(true)
                    ->after('artist_origin');
            }
        });

        $externalRoleId = DB::table('roles')->where('name', 'external_artist')->value('id');

        if ($externalRoleId) {
            $externalUserIds = DB::table('model_has_roles')
                ->where('role_id', $externalRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');

            if ($externalUserIds->isNotEmpty()) {
                DB::table('artists')
                    ->whereIn('user_id', $externalUserIds)
                    ->update([
                        'artist_origin' => 'external',
                        'has_public_profile' => false,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            if (Schema::hasColumn('artists', 'has_public_profile')) {
                $table->dropColumn('has_public_profile');
            }

            if (Schema::hasColumn('artists', 'artist_origin')) {
                $table->dropColumn('artist_origin');
            }
        });
    }
};
