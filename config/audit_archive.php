<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Audit archive and retention configuration
|--------------------------------------------------------------------------
|
| Destructive cleanup is disabled unless an authorized retention period is
| explicitly configured. No institutional retention duration is assumed here.
|
*/

return [
    'enabled' => env('PCDR_AUDIT_ARCHIVE_ENABLED', true),

    'disk' => env('PCDR_AUDIT_ARCHIVE_DISK', 'private'),

    'path_prefix' => trim(env('PCDR_AUDIT_ARCHIVE_PATH', 'audit-archives'), '/'),

    'schedule' => [
        'day' => max(1, min(28, (int) env('PCDR_AUDIT_ARCHIVE_DAY', '2'))),
        'time' => env('PCDR_AUDIT_ARCHIVE_TIME', '02:30'),
        'cleanup_time' => env('PCDR_AUDIT_CLEANUP_TIME', '03:30'),
    ],

    'export' => [
        'schema_version' => env('PCDR_AUDIT_EXPORT_SCHEMA_VERSION', '1.0'),
        'batch_size' => max(100, (int) env('PCDR_AUDIT_EXPORT_BATCH_SIZE', '500')),
        'application_version' => env('PCDR_DEPLOY_VERSION'),
        'git_commit' => env('PCDR_DEPLOY_COMMIT'),
    ],

    'retention' => [
        'enabled' => env('PCDR_AUDIT_RETENTION_ENABLED', false),
        'hot_months' => env('PCDR_AUDIT_RETENTION_MONTHS'),
        'require_verified_archive' => true,
        'require_dry_run' => env('PCDR_AUDIT_RETENTION_REQUIRE_DRY_RUN', true),
        'min_archive_age_days' => max(0, (int) env('PCDR_AUDIT_RETENTION_MIN_ARCHIVE_AGE_DAYS', '7')),
        'batch_size' => max(50, (int) env('PCDR_AUDIT_CLEANUP_BATCH_SIZE', '500')),
        'window' => [
            'start' => env('PCDR_AUDIT_CLEANUP_WINDOW_START', '01:00'),
            'end' => env('PCDR_AUDIT_CLEANUP_WINDOW_END', '05:00'),
        ],
    ],

    'notification' => [
        'emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PCDR_AUDIT_LIFECYCLE_EMAILS', '')),
        ))),
    ],

    'queue' => [
        'connection' => env('PCDR_AUDIT_QUEUE_CONNECTION'),
        'queue' => env('PCDR_AUDIT_QUEUE_NAME', 'audit'),
    ],
];
