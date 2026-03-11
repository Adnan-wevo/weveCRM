<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureDownloadController extends Controller
{
    private const ALLOWED_MODULES = ['users', 'roles', 'permissions', 'tenants'];

    /** Modules served under organisation-setup rather than access-control. */
    private const ORG_SETUP_MODULES = ['tenants'];

    /**
     * Download an export file.
     * Requires a valid signed URL + auth + the relevant module import-export permission.
     */
    public function export(Request $request, string $module, string $filename): StreamedResponse
    {
        abort_unless(in_array($module, self::ALLOWED_MODULES, true), 404);

        $isOrgSetup = in_array($module, self::ORG_SETUP_MODULES, true);
        $permission = $isOrgSetup
            ? "organisation-setup.{$module}.import-export"
            : "access-control.{$module}.import-export";

        // If the download was triggered from within a tenant, initialise tenancy so that
        // the permission check resolves the correct tenant-scoped permission record.
        if (($tenantId = $request->query('tenant')) && ! tenancy()->initialized) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        abort_unless(Gate::allows($permission), 403);

        $disk = config('filesystems.exports_disk', 'local');
        $tenantId = $request->query('tenant');
        $prefix = $tenantId ? "{$tenantId}/" : '';
        $path = $isOrgSetup
            ? "{$prefix}exports/organisation-setup/{$module}/{$filename}"
            : "{$prefix}exports/access-control/{$module}/{$filename}";

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Serve a private media file (access-control/{collection}/{uuid}/filename).
     * Streams the file inline (for use in <img> tags).
     * Requires auth only — served from the local (private) disk.
     */
    public function media(Request $request, string $path): StreamedResponse
    {
        abort_unless(str_starts_with($path, 'access-control/'), 403);

        $disk = config('media-library.disk_name', 'local');

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path);
    }
}
