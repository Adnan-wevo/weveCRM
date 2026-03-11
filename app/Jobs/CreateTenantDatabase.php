<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Events\CreatingDatabase;
use Stancl\Tenancy\Events\DatabaseCreated;

/**
 * Idempotent tenant database creation job (queued via Horizon).
 *
 * Replaces the stock Stancl\Tenancy\Jobs\CreateDatabase so that orphan
 * databases (left behind after `migrate:fresh --seed`) do not break the
 * pipeline. Uses CREATE DATABASE IF NOT EXISTS instead of throwing
 * TenantDatabaseAlreadyExistsException.
 *
 * This job runs in the queue worker (CLI context), completely avoiding
 * web-request issues with Debugbar/Telescope profiling dynamic connections.
 */
class CreateTenantDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly TenantWithDatabase $tenant) {}

    public function handle(DatabaseManager $databaseManager): void
    {
        event(new CreatingDatabase($this->tenant));

        if ($this->tenant->getInternal('create_database') === false) {
            return;
        }

        $this->tenant->database()->makeCredentials();

        $centralConnection = config('tenancy.database.central_connection');
        $database = $this->tenant->database()->getName();
        $charset = config("database.connections.{$centralConnection}.charset", 'utf8mb4');
        $collation = config("database.connections.{$centralConnection}.collation", 'utf8mb4_unicode_ci');

        // Idempotent: does nothing when the database already exists.
        DB::connection($centralConnection)
            ->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET `{$charset}` COLLATE `{$collation}`");

        event(new DatabaseCreated($this->tenant));
    }
}
