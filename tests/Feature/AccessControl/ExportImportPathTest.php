<?php

use App\Jobs\AccessControl\ExportPermissionsJob;
use App\Jobs\AccessControl\ExportRolesJob;
use App\Jobs\AccessControl\ExportUsersJob;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use Modules\OrganisationSetup\Jobs\ExportTenantsJob;

// ── Export jobs — file path ───────────────────────────────────────────

it('export permissions job stores file under tenant prefix when tenant id given', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'tenant-abc/exports/access-control/permissions/');
    });

    (new ExportPermissionsJob('job-1', 1, 'tenant-abc'))->handle();
});

it('export permissions job stores file without prefix when no tenant id', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'exports/access-control/permissions/');
    });

    (new ExportPermissionsJob('job-2', 1, null))->handle();
});

it('export roles job stores file under tenant prefix when tenant id given', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'tenant-abc/exports/access-control/roles/');
    });

    (new ExportRolesJob('job-3', 1, 'tenant-abc'))->handle();
});

it('export users job stores file under tenant prefix when tenant id given', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'tenant-xyz/exports/access-control/users/');
    });

    (new ExportUsersJob('job-4', 1, 'tenant-xyz'))->handle();
});

it('export tenants job stores file under tenant prefix when tenant id given', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'tenant-abc/exports/organisation-setup/tenants/');
    });

    (new ExportTenantsJob('job-5', 1, 'tenant-abc'))->handle();
});

it('export tenants job stores file without prefix when no tenant id', function () {
    Excel::shouldReceive('store')->once()->withArgs(function ($export, string $path) {
        return str_starts_with($path, 'exports/organisation-setup/tenants/');
    });

    (new ExportTenantsJob('job-6', 1, null))->handle();
});

// ── SecureDownloadController — exports ───────────────────────────────

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super-admin');
    $this->actingAs($user);
});

it('secure export serves file from tenant-prefixed path when tenant param present', function () {
    $disk = config('filesystems.exports_disk', 'local');
    Storage::fake($disk);

    Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);
    $filename = 'permissions_export_test.xlsx';
    Storage::disk($disk)->put("acme/exports/access-control/permissions/{$filename}", 'data');

    $url = URL::temporarySignedRoute('secure.export', now()->addMinutes(5), [
        'module' => 'permissions',
        'filename' => $filename,
        'tenant' => 'acme',
    ]);

    $this->get($url)->assertOk();
});

it('secure export returns 404 when file is not at tenant-prefixed path', function () {
    $disk = config('filesystems.exports_disk', 'local');
    Storage::fake($disk);

    Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);
    $filename = 'permissions_export_test.xlsx';
    // File stored at central path — must NOT be found when tenant param is given
    Storage::disk($disk)->put("exports/access-control/permissions/{$filename}", 'data');

    $url = URL::temporarySignedRoute('secure.export', now()->addMinutes(5), [
        'module' => 'permissions',
        'filename' => $filename,
        'tenant' => 'acme',
    ]);

    $this->get($url)->assertNotFound();
});

it('secure export serves file from central path when no tenant param', function () {
    $disk = config('filesystems.exports_disk', 'local');
    Storage::fake($disk);

    $filename = 'permissions_export_test.xlsx';
    Storage::disk($disk)->put("exports/access-control/permissions/{$filename}", 'data');

    $url = URL::temporarySignedRoute('secure.export', now()->addMinutes(5), [
        'module' => 'permissions',
        'filename' => $filename,
    ]);

    $this->get($url)->assertOk();
});
