<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modelos usados por Spatie Permission
    |--------------------------------------------------------------------------
    */

    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role'       => Spatie\Permission\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de tablas
    |--------------------------------------------------------------------------
    */

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de columnas
    |--------------------------------------------------------------------------
    */

    'column_names' => [
        'role_pivot_key'        => null,
        'permission_pivot_key'  => null,
        'model_morph_key'       => 'model_id',
        'team_foreign_key'      => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard predeterminado
    |--------------------------------------------------------------------------
    | ⚠️ Para Jetstream y Sanctum, siempre debe ser "web"
    */

    'default_guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Registro de chequeo de permisos y eventos
    |--------------------------------------------------------------------------
    */

    'register_permission_check_method' => true,
    'register_octane_reset_listener'   => false,
    'events_enabled'                   => false,

    /*
    |--------------------------------------------------------------------------
    | Configuración de equipos (teams)
    |--------------------------------------------------------------------------
    */

    'teams'          => false,
    'team_resolver'  => \Spatie\Permission\DefaultTeamResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Passport / Client Credentials
    |--------------------------------------------------------------------------
    */

    'use_passport_client_credentials' => false,

    /*
    |--------------------------------------------------------------------------
    | Seguridad en mensajes de excepción
    |--------------------------------------------------------------------------
    */

    'display_permission_in_exception' => false,
    'display_role_in_exception'       => false,

    /*
    |--------------------------------------------------------------------------
    | Wildcards
    |--------------------------------------------------------------------------
    */

    'enable_wildcard_permission' => false,
    // 'wildcard_permission' => Spatie\Permission\WildcardPermission::class,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key'             => 'spatie.permission.cache',
        'store'           => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards permitidos (debe incluir "web" para Jetstream)
    |--------------------------------------------------------------------------
    */

    'allowed_guards' => ['web'],
];
