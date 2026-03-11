<?php

namespace Modules\OrganisationSetup\Livewire\Tenants;

use App\Actions\SyncRolesPermissionsToTenantAction;
use App\Models\Tenant;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class SyncMigrationsModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public bool $withSeed = true;

    public bool $withPermissionSync = true;

    /** @var 'idle'|'running'|'done'|'failed' */
    public string $status = 'idle';

    public string $resultMessage = '';

    /** @var array<int, array{tenant: string, error: string}> */
    public array $errorDetails = [];

    #[On('open-sync-migrations')]
    public function open(): void
    {
        $this->authorize('organisation-setup.tenants.sync-migrations');
        $this->reset('status', 'resultMessage', 'errorDetails');
        $this->withSeed = true;
        $this->withPermissionSync = true;
        $this->show = true;
    }

    public function sync(): void
    {
        $this->authorize('organisation-setup.tenants.sync-migrations');

        $this->status = 'running';

        /** @var \Illuminate\Database\Eloquent\Collection<int, Tenant> $tenants */
        $tenants = Tenant::all();

        if ($tenants->count() === 0) {
            $this->status = 'done';
            $this->resultMessage = __('No tenants found.');

            return;
        }

        $migrated = 0;
        $seeded = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            try {
                tenancy()->initialize($tenant);

                $migrationPath = database_path('migrations/tenant');

                $exitCode = Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => [$migrationPath],
                    '--realpath' => true,
                    '--force' => true,
                ]);

                if ($exitCode !== 0) {
                    $output = trim(Artisan::output());
                    Log::warning("[SyncMigrations] Migration failed for tenant {$tenant->getTenantKey()}: {$output}");
                    $this->errorDetails[] = ['tenant' => $tenant->getTenantKey(), 'error' => "Migration failed: {$output}"];
                    $failed++;
                    tenancy()->end();

                    continue;
                }

                $migrated++;

                if ($this->withSeed) {
                    $seedExit = Artisan::call('db:seed', [
                        '--class' => TenantDatabaseSeeder::class,
                        '--database' => 'tenant',
                        '--force' => true,
                    ]);

                    if ($seedExit === 0) {
                        $seeded++;
                    } else {
                        $seedOutput = trim(Artisan::output());
                        Log::warning("[SyncMigrations] Seed failed for tenant {$tenant->getTenantKey()}: {$seedOutput}");
                        $this->errorDetails[] = ['tenant' => $tenant->getTenantKey(), 'error' => "Seed failed: {$seedOutput}"];
                    }
                }

                tenancy()->end();
            } catch (\Throwable $e) {
                Log::error("[SyncMigrations] Error for tenant {$tenant->getTenantKey()}: {$e->getMessage()}");
                $this->errorDetails[] = ['tenant' => $tenant->getTenantKey(), 'error' => $e->getMessage()];
                $failed++;

                try {
                    tenancy()->end();
                } catch (\Throwable) {
                    // Already ended or never initialised.
                }
            }
        }

        // Sync permissions to tenants if requested
        if ($this->withPermissionSync) {
            $action = app(SyncRolesPermissionsToTenantAction::class);

            foreach ($tenants as $tenant) {
                /** @var Tenant $tenant */
                $action->execute($tenant);
            }
        }

        $this->status = $failed > 0 ? 'failed' : 'done';

        $parts = [];
        $parts[] = __(':count tenant(s) migrated', ['count' => $migrated]);

        if ($this->withSeed) {
            $parts[] = __(':count seeded', ['count' => $seeded]);
        }

        if ($this->withPermissionSync) {
            $parts[] = __('permissions synced');
        }

        if ($failed > 0) {
            $parts[] = __(':count failed', ['count' => $failed]);
        }

        $this->resultMessage = implode(', ', $parts).'.';

        $this->dispatch('notify', type: $failed > 0 ? 'warning' : 'success', message: $this->resultMessage);
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset('status', 'resultMessage', 'errorDetails');
    }

    public function render(): View
    {
        return view('organisationsetup::livewire.tenants.sync-migrations-modal');
    }
}
