<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureCoreRoles();

        Gate::define('trash.view.content', fn(User $user) => $user->hasAnyRole(['admin', 'contentmanager']));
        Gate::define('trash.manage.content', fn(User $user) => $user->hasRole('admin'));

        Gate::define('trash.view.team', fn(User $user) => $user->hasAnyRole(['admin', 'contentmanager']));
        Gate::define('trash.manage.team', fn(User $user) => $user->hasRole('admin'));

        Gate::define('trash.view.events', fn(User $user) => $user->hasRole('admin'));
        Gate::define('trash.manage.events', fn(User $user) => $user->hasRole('admin'));

        Gate::define('trash.view.royalties', fn(User $user) => $user->hasRole('admin'));
        Gate::define('trash.manage.royalties', fn(User $user) => $user->hasRole('admin'));

        Gate::define('audit.view', fn(User $user) => $user->hasRole('admin'));
    }

    private function ensureCoreRoles(): void
    {
        try {
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
            if (empty($missing)) {
                return;
            }

            $rows = array_map(static fn(string $roleName) => [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], $missing);

            DB::table('roles')->insert($rows);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Si la conexión a DB no está disponible durante bootstrap (CLI/sandbox),
            // no bloqueamos la ejecución del framework.
            return;
        }
    }
}
