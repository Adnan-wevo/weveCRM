# Files and Storage

## Overview

The application uses Laravel's filesystem abstraction with multiple disk configurations. MinIO provides S3-compatible object storage in development. Spatie Media Library handles file attachments on Eloquent models. Secure downloads use signed URLs to prevent unauthorized file access.

## Filesystem Disks

Configured in `config/filesystems.php`:

| Disk | Driver | Purpose |
|------|--------|---------|
| `local` | `local` | Private file storage (`storage/app/private`) |
| `public` | `local` | Publicly accessible files (`storage/app/public`, symlinked to `public/storage`) |
| `s3` | `s3` | S3-compatible storage (MinIO in development) |
| `minio` | `s3` | Explicit MinIO configuration (mirrors `s3` disk) || `file-transfers` | `local` | Central-only disk for export files and import uploads (`storage/app/file-transfers`) || `livewire-tmp` | `local` | Temporary files for Livewire file uploads (`storage/app/livewire-tmp`) |

### Default Disk

The default filesystem disk is set via `FILESYSTEM_DISK` environment variable (defaults to `local`).

### Export / Import Disk

An independent `EXPORTS_DISK` environment variable controls where export files are written and where uploaded import files are stored:

```env
# Local development (no MinIO)
EXPORTS_DISK=file-transfers

# Docker / production with MinIO
EXPORTS_DISK=s3
```

The `file-transfers` disk (local driver, `storage/app/file-transfers`) is intentionally **excluded** from `tenancy.filesystem.disks`. This means `FilesystemTenancyBootstrapper` never alters its root. Tenant isolation is achieved manually by prefixing file paths with the tenant ID (e.g. `{tenantId}/exports/access-control/users/file.xlsx`). When `EXPORTS_DISK=s3` (Docker / production), MinIO is used instead and is also unaffected by the filesystem bootstrapper.

### S3 / MinIO Configuration

```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=wevetel
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/wevetel
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Docker Networking**: The application container accesses MinIO at `http://minio:9000` (internal Docker network). The browser accesses MinIO at `http://localhost:9000` (host port mapping). `AWS_URL` is set to the external URL for generating public file URLs.

### MinIO Bucket Provisioning

The `createbuckets` service in `docker-compose.dev.yml` automatically creates the `wevetel` bucket on first boot:

```bash
mc alias set myminio http://minio:9000 minioadmin minioadmin
mc mb myminio/wevetel
mc anonymous set public myminio/wevetel
```

## Spatie Media Library

### Configuration

`config/media-library.php`:

| Setting | Value |
|---------|-------|
| Disk | `s3` (MinIO in development) |
| Max file size | Configurable |
| Queue | Conversions can be queued |

### Custom File Namer

`app/Support/RandomFileNamer.php`:

Generates random file names for uploaded media to prevent predictable URLs and filename collisions:

```php
class RandomFileNamer extends DefaultFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return Str::random(32);
    }
}
```

### Custom Path Generator

`app/Support/AccessControlPathGenerator.php`:

Organizes uploaded files into tenant-scoped and model-scoped directories:

```
{tenant_id}/{model_type}/{model_id}/
```

This ensures file paths are unique per tenant and model, preventing cross-tenant file access.

### Usage in Models

Models that accept file attachments use the `HasMedia` interface and `InteractsWithMedia` trait:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile();
    }
}
```

## Secure Downloads

### SecureDownloadController

`app/Http/Controllers/SecureDownloadController.php`:

Provides authenticated and signed file download endpoints:

```php
Route::get('/secure-download/{path}', [SecureDownloadController::class, 'download'])
    ->name('secure-download')
    ->middleware('signed');
```

**Flow**:

1. Application generates a signed URL with an expiration time.
2. User clicks the signed URL.
3. Middleware validates the signature and expiration.
4. Controller streams the file from storage via `Storage::download()`.
5. File is never directly exposed in the public directory.

### Generating Signed Download URLs

```php
use Illuminate\Support\Facades\URL;

$url = URL::temporarySignedRoute(
    'secure-download',
    now()->addMinutes(30),
    ['path' => $filePath]
);
```

## Import/Export File Handling

### Export Files

Export operations produce `.xlsx` files via Maatwebsite Excel and a queued job:

1. The Livewire `ImportExportModal` dispatches a queued export job with a UUID job ID.
2. The job writes the file to the `EXPORTS_DISK` disk under a tenant-aware path:
   - Tenant context:  `{tenantId}/exports/{module-namespace}/{module}/{filename}.xlsx`
   - Central context: `exports/{module-namespace}/{module}/{filename}.xlsx`
3. The job stores the signed download URL in `Cache::store(config('cache.default'))` with key `export_job_{$jobId}` (30-minute TTL).
4. The Livewire component polls `checkJobStatuses()` every 3 seconds.
5. When the cache key is populated, the component presents a download link to the user.
6. The user clicks the signed URL, which is handled by `SecureDownloadController@export`.

### Import Files

Import operations process uploaded Excel/CSV files:

1. The user uploads a file via the Livewire `ImportExportModal` component.
2. The file is stored on the `EXPORTS_DISK` disk under a tenant-aware path:
   - Tenant context:  `{tenantId}/{module-namespace}/{module}/imports/{file}`
   - Central context: `{module-namespace}/{module}/imports/{file}`
3. An import job is dispatched with the stored file path and a UUID job ID.
4. The job processes the file via `Excel::import()`, collecting row-level failures.
5. The result (status, failure count) is written to `Cache::store(config('cache.default'))` with key `import_job_{$jobId}`.
6. The Livewire component polls and displays success or error feedback.

## Upload Limits

| Setting | Value | Configured In |
|---------|-------|---------------|
| PHP `upload_max_filesize` | 50 MB | `docker/php/php.ini` |
| PHP `post_max_size` | 50 MB | `docker/php/php.ini` |
| Nginx `client_max_body_size` | Configured in Nginx | `docker/nginx/default.conf` |
| Livewire temp disk | `storage/app/livewire-tmp` | `config/livewire.php` |

## Storage Symlink

The `public` disk is symlinked to `public/storage`:

```bash
php artisan storage:link
```

This is executed automatically by the entrypoint script during container startup.

## Tenant File Isolation

The `FilesystemTenancyBootstrapper` scopes filesystem operations to tenant-specific subdirectories:

- When tenancy is initialized, the root of the `local` and `public` disks is prefixed with the tenant identifier.
- Files stored by one tenant are not accessible to another.
- The S3 disk uses the custom `AccessControlPathGenerator` to achieve similar isolation at the path level.

### Disks Excluded from Tenant Bootstrapping

Two disks are deliberately **excluded** from `tenancy.filesystem.disks` and are never altered by `FilesystemTenancyBootstrapper`:

| Disk | Reason |
|------|--------|
| `file-transfers` | Export files and import uploads — tenant isolation is handled manually via path prefixes (`{tenantId}/…`) |
| `livewire-tmp` | Livewire temporary uploads — must remain accessible across all requests regardless of tenant context |

If you add a new disk that should remain central, ensure it is not listed in `config/tenancy.php` under `filesystem.disks`.

## SecureDownloadController

`app/Http/Controllers/SecureDownloadController.php` serves export files over signed URLs:

- Route: `GET /secure/exports/{module}/{filename}` (named `secure.export`).
- Requires a valid Laravel temporary signed URL (`?expires=…&signature=…`).
- Requires the authenticated user to have the `{namespace}.{module}.import-export` permission.
- Accepts an optional `?tenant={tenantId}` query parameter. When present, the controller initializes tenancy so that the permission check resolves in the correct tenant context, and constructs the file path with the `{tenantId}/` prefix.
- Streams the file from the `EXPORTS_DISK` disk.

```php
// Signed URL generation in export job
URL::temporarySignedRoute('secure.export', now()->addMinutes(30), array_filter([
    'module'   => 'users',
    'filename' => $filename,
    'tenant'   => $this->tenantId, // null omitted by array_filter
]));
```

## Adding New Storage Disks

1. Define the disk in `config/filesystems.php`.
2. Add corresponding environment variables to `.env` and `.env.example`.
3. If the disk is tenant-scoped, configure it in the tenancy bootstrapper.

```php
'custom' => [
    'driver' => 's3',
    'key'    => env('CUSTOM_AWS_ACCESS_KEY_ID'),
    'secret' => env('CUSTOM_AWS_SECRET_ACCESS_KEY'),
    'region' => env('CUSTOM_AWS_DEFAULT_REGION'),
    'bucket' => env('CUSTOM_AWS_BUCKET'),
],
```
