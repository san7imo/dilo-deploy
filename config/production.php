<?php

return [
    /**
     * Configuración de Producción para Dilo Records
     * 
     * Este archivo contiene todas las configuraciones recomendadas para producción.
     * Actualiza los valores según tu entorno.
     */

    'production' => [
        // === SEGURIDAD ===
        'app_debug' => false,
        'force_https' => true,
        'trusted_proxies' => '*',
        'trusted_hosts' => env('TRUSTED_HOSTS', 'example.com,www.example.com'),

        // === PERFORMANCE ===
        'cache_enabled' => true,
        'cache_duration' => 3600, // 1 hora
        'query_cache_duration' => 1800, // 30 minutos
        'view_cache' => true,

        // === DATABASE ===
        'database_pool_size' => 10,
        'database_pool_timeout' => 30,

        // === QUEUE ===
        'queue_enabled' => true,
        'queue_timeout' => 360,
        'queue_failed_job_retention' => 7 * 24 * 60 * 60, // 7 días

        // === LOGGING ===
        'log_level' => 'info',
        'log_rotation' => 'daily',
        'log_retention_days' => 30,

        // === SESSIONS ===
        'session_lifetime' => 120, // minutos
        'session_secure' => true,
        'session_http_only' => true,
        'session_same_site' => 'Lax',

        // === MAIL ===
        'mail_queue_enabled' => true,

        // === CORS ===
        'cors_enabled' => true,
        'cors_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://example.com')),

        // === IMAGEKIT ===
        'imagekit_enabled' => true,
        'imagekit_cache_duration' => 86400 * 30, // 30 días

        // === API LIMITS ===
        'rate_limit_enabled' => true,
        'rate_limit_per_minute' => 60,
        'rate_limit_burst' => 100,

        // === MAINTENANCE ===
        'maintenance_window_start' => '02:00', // UTC
        'maintenance_window_duration' => 30, // minutos
    ],

    'staging' => [
        'app_debug' => false,
        'force_https' => true,
        'cache_enabled' => true,
        'cache_duration' => 1800,
        'queue_enabled' => true,
    ],

    'development' => [
        'app_debug' => true,
        'force_https' => false,
        'cache_enabled' => false,
        'queue_enabled' => false,
    ],

    'testing' => [
        'app_debug' => true,
        'force_https' => false,
        'cache_enabled' => false,
        'queue_enabled' => false,
    ],
];
