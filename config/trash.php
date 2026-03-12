<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retention For Soft-Deleted Records
    |--------------------------------------------------------------------------
    |
    | Número de días que un registro puede permanecer en papelera antes de
    | ser eliminado permanentemente por la tarea programada.
    |
    */
    'purge_after_days' => (int) env('TRASH_PURGE_AFTER_DAYS', 60),

    /*
    |--------------------------------------------------------------------------
    | Scheduler Settings
    |--------------------------------------------------------------------------
    */
    'schedule_time' => env('TRASH_PURGE_SCHEDULE_TIME', '03:00'),
    'schedule_timezone' => env('TRASH_PURGE_SCHEDULE_TZ', env('APP_TIMEZONE', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | Cantidad de registros leídos por chunk por cada modelo para el purge.
    |
    */
    'batch_size' => (int) env('TRASH_PURGE_BATCH_SIZE', 200),
];

