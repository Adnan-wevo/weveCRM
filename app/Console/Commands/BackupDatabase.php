<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database
        {--tenant= : Backup a specific tenant database by ID}
        {--central-only : Only backup the central database}
        {--retention=30 : Number of days to retain backups}';

    protected $description = 'Backup the central database and all tenant databases (when TENANCY_MULTI_DB=true) to S3/MinIO';

    public function handle(): int
    {
        $date = Carbon::now()->format('Y-m-d_His');
        $retention = (int) $this->option('retention');
        $disk = Storage::disk(config('filesystems.backup_disk', 'minio'));
        $s3Prefix = 'backups';

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Temp directory for mariadb-dump output before uploading to S3
        $tmpDir = sys_get_temp_dir().'/db-backups';
        File::ensureDirectoryExists($tmpDir, 0755, true);

        // ── Central database ────────────────────────────────────────────
        if (! $this->option('tenant')) {
            $centralDb = config('database.connections.mysql.database');
            $filename = "{$centralDb}_{$date}.sql.gz";

            $this->info("Backing up central database: {$centralDb}");

            if (! $this->dumpAndUpload($host, $port, $username, $password, $centralDb, $tmpDir, $filename, $disk, $s3Prefix)) {
                return self::FAILURE;
            }
        }

        if ($this->option('central-only')) {
            $this->cleanOldBackups($disk, $s3Prefix, $retention);
            $this->info('Central backup completed.');

            return self::SUCCESS;
        }

        // ── Tenant databases ────────────────────────────────────────────
        $multiDb = (bool) config('tenancy.database.multi_db', false);

        if (! $multiDb) {
            $this->info('TENANCY_MULTI_DB is disabled — skipping tenant databases.');
            $this->cleanOldBackups($disk, $s3Prefix, $retention);
            $this->info('Backup completed.');

            return self::SUCCESS;
        }

        $prefix = config('tenancy.database.prefix', 'tenant');
        $suffix = config('tenancy.database.suffix', '');

        $query = Tenant::query();

        if ($specificTenant = $this->option('tenant')) {
            $query->where('id', $specificTenant);
        }

        /** @var \Illuminate\Support\Collection<int, Tenant> $tenants */
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to backup.');
            $this->cleanOldBackups($disk, $s3Prefix, $retention);

            return self::SUCCESS;
        }

        $this->info("Backing up {$tenants->count()} tenant database(s)...");

        $failed = 0;

        foreach ($tenants as $tenant) {
            $tenantDb = $prefix.$tenant->id.$suffix;
            $filename = "{$tenantDb}_{$date}.sql.gz";

            $this->info("  Backing up tenant: {$tenant->id} ({$tenantDb})");

            if (! $this->dumpAndUpload($host, $port, $username, $password, $tenantDb, $tmpDir, $filename, $disk, $s3Prefix)) {
                $failed++;

                continue;
            }
        }

        $this->cleanOldBackups($disk, $s3Prefix, $retention);

        if ($failed > 0) {
            $this->error("{$failed} tenant backup(s) failed.");

            return self::FAILURE;
        }

        $this->info('All backups completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Dump a database to a gzip file locally, upload to S3, then remove the local temp file.
     *
     * @param  \Illuminate\Filesystem\FilesystemAdapter  $disk
     */
    private function dumpAndUpload(string $host, string $port, string $username, string $password, string $database, string $tmpDir, string $filename, $disk, string $s3Prefix): bool
    {
        $localPath = "{$tmpDir}/{$filename}";

        $command = sprintf(
            'mariadb-dump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --quick %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($localPath),
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            $this->error("  ✗ Failed to dump {$database}: ".trim($result->errorOutput()));
            File::delete($localPath);

            return false;
        }

        // Upload to S3/MinIO
        $s3Path = "{$s3Prefix}/{$filename}";

        try {
            $stream = fopen($localPath, 'rb');

            if ($stream === false) {
                $this->error("  ✗ Cannot read temp file: {$localPath}");

                return false;
            }

            $disk->put($s3Path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $size = $disk->size($s3Path);
            $this->info("  → s3://{$s3Prefix}/{$filename} (".$this->humanSize($size).')');
        } catch (\Throwable $e) {
            $this->error("  ✗ Failed to upload {$filename} to S3: ".$e->getMessage());
            File::delete($localPath);

            return false;
        }

        // Remove local temp file
        File::delete($localPath);

        return true;
    }

    /**
     * Remove backup files older than the retention period from S3.
     *
     * @param  \Illuminate\Filesystem\FilesystemAdapter  $disk
     */
    private function cleanOldBackups($disk, string $s3Prefix, int $retentionDays): void
    {
        $cutoff = Carbon::now()->subDays($retentionDays);
        $deleted = 0;

        /** @var array<int, string> $files */
        $files = $disk->files($s3Prefix);

        foreach ($files as $file) {
            if (! str_ends_with($file, '.sql.gz')) {
                continue;
            }

            $lastModified = $disk->lastModified($file);

            if (Carbon::createFromTimestamp($lastModified)->lt($cutoff)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned {$deleted} backup(s) older than {$retentionDays} days from S3.");
        }
    }

    /**
     * Format bytes to a human-readable string.
     */
    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
