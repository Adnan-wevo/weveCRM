<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root seeder for tenant databases.
 *
 * This seeder is used instead of DatabaseSeeder when running seeds on
 * tenant databases. It only calls seeders that operate on tenant-specific
 * tables — never central tables like permissions or roles.
 *
 * Add tenant-only seeders to the $this->call() array below.
 */
class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant database.
     */
    public function run(): void
    {
        // Register tenant-specific seeders here.
        // Example:
        // $this->call([
        //     TestProductSeeder::class,
        // ]);
    }
}
