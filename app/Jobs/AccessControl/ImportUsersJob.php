<?php

namespace App\Jobs\AccessControl;

use App\Imports\AccessControl\UsersImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $filePath,
        private readonly string $jobId,
        private readonly int $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $import = new UsersImport;
        Excel::import($import, $this->filePath, config('filesystems.exports_disk', 'local'));

        $failures = $import->getFailures();

        Cache::store(config('cache.default'))->put("import_job_{$this->jobId}", [
            'status' => 'done',
            'imported' => true,
            'failure_count' => count($failures),
        ], now()->addMinutes(30));
    }

    public function failed(\Throwable $exception): void
    {
        Cache::store(config('cache.default'))->put("import_job_{$this->jobId}", [
            'status' => 'failed',
            'message' => $exception->getMessage(),
        ], now()->addMinutes(30));
    }
}
