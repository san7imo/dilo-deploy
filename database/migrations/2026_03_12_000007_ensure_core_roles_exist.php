<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $coreRoles = ['admin', 'artist', 'external_artist', 'roadmanager', 'contentmanager'];

        $existing = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $coreRoles)
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff($coreRoles, $existing));
        if (!empty($missing)) {
            $rows = array_map(static fn(string $roleName) => [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], $missing);

            DB::table('roles')->insert($rows);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // No-op: no eliminamos roles base para evitar romper usuarios existentes.
    }
};
