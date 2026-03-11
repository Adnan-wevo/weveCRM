<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Run tenant migrations after a new tenant database is created.
 *
 * Replaces the stock Stancl\Tenancy\Jobs\MigrateDatabase. Instead of
 * routing through `tenants:migrate` (which passes --database=null and
 * relies on the singleton Migrator's state), this job:
 *
 * 1. Initialises tenancy — sets default connection to 'tenant'
 * 2. Calls the standard `migrate` command with --database=tenant explicitly
 * 3. Ends tenancy — reverts to central context
 *
 * This guarantees the Migrator, its repository, and Schema all use the
 * correct tenant connection regardless of singleton state in long-running
 * Horizon workers.
 */
class MigrateTenantDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly TenantWithDatabase $tenant) {}

    public function handle(): void
    {
        $tenantKey = $this->tenant->getTenantKey();

        Log::info("[MigrateTenantDatabase] Starting for tenant: {$tenantKey}");

        // Initialise tenancy — creates the `tenant` DB connection config
        // and sets it as the default connection.
        tenancy()->initialize($this->tenant);

        $migrationPath = database_path('migrations/tenant');

        // Use the standard `migrate` command with an EXPLICIT --database
        // so the Migrator calls setConnection('tenant') — not null.
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => [$migrationPath],
            '--realpath' => true,
            '--force' => true,
        ]);

        $output = trim(Artisan::output());

        Log::info("[MigrateTenantDatabase] Tenant {$tenantKey} — exit: {$exitCode}, output: {$output}");

        // Revert to central context.
        tenancy()->end();
    }
}
