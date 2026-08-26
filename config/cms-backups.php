<?php

return [
    'enabled' => env('CMS_BACKUPS_ENABLED', true),
    'local_disk' => env('CMS_BACKUPS_LOCAL_DISK', 'local'),
    'local_path' => env('CMS_BACKUPS_LOCAL_PATH', 'cms-backups'),
    'temporary_path' => env('CMS_BACKUPS_TEMPORARY_PATH', storage_path('app/cms-backups/tmp')),
    'default_timezone' => env('APP_TIMEZONE', 'Europe/Lisbon'),
    'allow_short_frequencies' => env('CMS_BACKUPS_ALLOW_SHORT_FREQUENCIES', false),
    'default_included_paths' => [
        'storage/app/public',
        'public/uploads',
        'public/media',
    ],
    'default_excluded_paths' => [
        'vendor',
        'node_modules',
        '.git',
        '.env',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
    ],
    'notifications' => [
        'events' => [
            'backup_failed' => true,
            'backup_missing' => true,
            'backup_corrupted' => true,
            'remote_upload_failed' => true,
            'backup_succeeded' => false,
            'retention_deleted' => false,
            'recovered' => true,
        ],
        'repeat_alert_after_minutes' => 360,
        'missing_grace_minutes' => 60,
    ],
    'retry' => [
        'tries' => 3,
        'backoff' => [60, 300, 900],
    ],
];
