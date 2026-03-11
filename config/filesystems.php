<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /**
         * Central-only disk for queued export files and import uploads.
         * NOT included in tenancy.filesystem.disks so FilesystemTenancyBootstrapper
         * never alters its root. Tenant isolation is handled manually by prefixing
         * paths with the tenant ID (e.g. {tenantId}/exports/…).
         */
        'file-transfers' => [
            'driver' => 'local',
            'root' => storage_path('app/file-transfers'),
            'throw' => false,
            'report' => false,
        ],

        /**
         * Central-only disk for Livewire temporary file uploads.
         * NOT included in tenancy.filesystem.disks so the FilesystemTenancyBootstrapper
         * never alters its root — temp upload files remain reachable across all requests
         * even after tenancy is initialised mid-request (e.g. via PersistentMiddleware).
         */
        'livewire-tmp' => [
            'driver' => 'local',
            'root' => storage_path('app/livewire-tmp'),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // MinIO / S3-compatible — used for import & export files.
        // AWS_ENDPOINT should point to MinIO (e.g. http://minio:9000 in Docker).
        // AWS_USE_PATH_STYLE_ENDPOINT must be true for MinIO.
        'minio' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export & Import Disk
    |--------------------------------------------------------------------------
    |
    | The disk used to store queued export files and temporary import uploads.
    | Set EXPORTS_DISK=minio (Docker) or EXPORTS_DISK=local (without S3).
    |
    */

    'exports_disk' => env('EXPORTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Database Backup Disk
    |--------------------------------------------------------------------------
    |
    | The disk used to store database backups created by app:backup-database.
    | Defaults to MinIO. Set BACKUP_DISK=local to store backups locally.
    |
    */

    'backup_disk' => env('BACKUP_DISK', 'minio'),

];
