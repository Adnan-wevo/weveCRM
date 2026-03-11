<?php

namespace Modules\OrganisationSetup\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\OrganisationSetup\Exports\TenantsExport;

class ExportTenantsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $jobId,
        private readonly int $userId,
        private readonly ?string $tenantId = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $disk = config('filesystems.exports_disk', 'local');
        $filename = 'tenants_export_'.now()->format('Ymd_His').'_'.Str::random(12).'.xlsx';
        $prefix = $this->tenantId ? "{$this->tenantId}/" : '';
        $path = "{$prefix}exports/organisation-setup/tenants/{$filename}";

        Excel::store(new TenantsExport, $path, $disk);

        Cache::store(config('cache.default'))->put("export_job_{$this->jobId}", [
            'status' => 'done',
            'url' => URL::temporarySignedRoute('secure.export', now()->addMinutes(30), array_filter([
                'module' => 'tenants',
                'filename' => $filename,
                'tenant' => $this->tenantId,
            ])),

            'filename' => $filename,
        ], now()->addMinutes(30));
    }

    public function failed(\Throwable $exception): void
    {
        Cache::store(config('cache.default'))->put("export_job_{$this->jobId}", [
            'status' => 'failed',
            'message' => $exception->getMessage(),
        ], now()->addMinutes(30));
    }
}
