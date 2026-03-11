<?php

namespace App\Providers;

use App\Jobs\CreateTenantDatabase;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        $multiDb = (bool) config('tenancy.database.multi_db', false);

        $tenantCreatedListeners = [];
        $tenantDeletedListeners = [];

        if ($multiDb) {
            // Multi-DB: create the tenant database and run migrations via queue.
            // Queued pipeline ensures jobs run in Horizon's CLI context, avoiding
            // web-request issues with Debugbar/Telescope profiling dynamic connections.
            // Uses CreateTenantDatabase (idempotent) instead of stock CreateDatabase
            // so orphan DBs left after `migrate:fresh` don't break the pipeline.
            $tenantCreatedListeners[] = JobPipeline::make([
                CreateTenantDatabase::class,
                \App\Jobs\MigrateTenantDatabase::class,
            ])->send(function (Events\TenantCreated $event) {
                return $event->tenant;
            })->shouldBeQueued()->toListener();

            $tenantDeletedListeners[] = JobPipeline::make([
                Jobs\DeleteDatabase::class,
            ])->send(function (Events\TenantDeleted $event) {
                return $event->tenant;
            })->toListener();
        }

        // Always: sync roles/permissions from central templates.
        $tenantCreatedListeners[] = \App\Listeners\SyncRolesPermissionsForNewTenant::class;

        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => $tenantCreatedListeners,

            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => $tenantDeletedListeners,

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $mode = config('tenancy.mode', 'single');

        if ($mode === 'single') {
            return;
        }

        // In single-database tenancy, tenant migrations must also run against
        // the central database so the tables exist in the shared DB.
        if (! config('tenancy.database.multi_db', false)) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }

        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
        $this->registerLivewirePersistentMiddleware();
    }

    /**
     * Register tenancy-initialising middleware as Livewire persistent middleware.
     *
     * Livewire's /update requests are central routes and bypass InitializeTenancyByPath.
     * By adding it to the persistent middleware list, Livewire re-runs it on every
     * component update request using the original page URL stored in the snapshot memo,
     * ensuring tenant context is always initialised inside component actions.
     */
    protected function registerLivewirePersistentMiddleware(): void
    {
        $modeMiddleware = [
            'path' => Middleware\InitializeTenancyByPath::class,
            'subdomain' => Middleware\InitializeTenancyByDomain::class,
        ];

        $mode = config('tenancy.mode', 'single');

        if (isset($modeMiddleware[$mode])) {
            Livewire::addPersistentMiddleware($modeMiddleware[$mode]);
        }
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes(): void
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
