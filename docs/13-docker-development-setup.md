# Docker Development Setup

## Overview

The development environment consists of 10 Docker services orchestrated via `docker-compose.dev.yml`. A single `Dockerfile.dev` builds the application container with PHP 8.4-FPM, Node.js 24, and all required extensions. Supervisor manages three processes inside the container: PHP-FPM, Horizon, and Reverb.

## Dockerfile

### Base Image

```dockerfile
FROM php:8.4-fpm
```

### System Packages

The following OS-level packages are installed:

- Nginx
- Supervisor
- Cron
- Node.js 24 (via NodeSource)
- MariaDB client
- Image processing libraries (libpng, libjpeg, libwebp, libfreetype, libzip)
- Git and Unzip

### PHP Extensions

| Extension | Purpose |
|-----------|---------|
| `pdo_mysql` | MariaDB connectivity |
| `gd` (PNG, JPEG, WebP, FreeType) | Image processing for media library |
| `zip` | Archive handling |
| `pcntl` | Process control (Horizon, Reverb) |
| `sockets` | WebSocket support (Reverb) |
| `bcmath` | Arbitrary precision math |
| `intl` | Internationalization |
| `opcache` | Bytecode caching |
| `redis` | Redis connectivity |

Redis is installed via PECL. OPcache is configured via a custom `php.ini`.

### PHP Configuration

Key `php.ini` overrides:

| Setting | Value |
|---------|-------|
| `upload_max_filesize` | 50M |
| `post_max_size` | 50M |
| `memory_limit` | 256M |
| `max_execution_time` | 300 |
| `opcache.enable` | 1 |
| `opcache.memory_consumption` | 128 |
| `opcache.max_accelerated_files` | 10000 |

### Composer

Composer is copied from the official `composer:latest` image.

## Container Architecture

### Process Management

Supervisor runs three long-lived processes:

| Program | Command | Auto-Restart |
|---------|---------|-------------|
| `php-fpm` | `php-fpm --nodaemonize` | Yes |
| `horizon` | `php artisan horizon` | Yes |
| `reverb` | `php artisan reverb:start --host=0.0.0.0 --port=8080` | Yes |

All processes log to `/dev/stdout` and `/dev/stderr`.

### Cron

A cron entry runs the Laravel scheduler every minute:

```
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Nginx

Nginx serves as the HTTP reverse proxy:

- Listens on port 80 inside the container.
- Routes PHP requests to `php-fpm` via Unix socket or TCP.
- Proxies `/app/*` WebSocket connections to Reverb on port 8080.
- Proxies `/@vite/*` and related paths to the Vite dev server for HMR.
- Sets `index.php` as the default handler with standard Laravel `try_files` rewrite.

## Docker Compose Services

### Application Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| `app` | Built from `Dockerfile.dev` | `8888:80` | Laravel application (PHP-FPM + Nginx + Horizon + Reverb) |
| `vite` | Same image, `npm run dev` | `5173:5173` | Vite HMR dev server |

### Infrastructure Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| `mariadb` | `mariadb:11.4` | `3306:3306` | Primary database |
| `redis` | `redis:7.4-alpine` | `6379:6379` | Cache, queues, sessions, broadcasting |
| `minio` | `minio/minio:latest` | `9000:9000`, `9001:9001` | S3-compatible object storage |
| `createbuckets` | `minio/mc:latest` | -- | MinIO bucket initialization (runs once) |

### Authentication Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| `keycloak` | `quay.io/keycloak/keycloak:26.1.4` | `8080:8080` | Identity provider (OIDC SSO) |
| `keycloak-db` | `postgres:17-alpine` | `5432:5432` | Keycloak database |
| `keycloak-setup` | `curlimages/curl:latest` | -- | Automated Keycloak realm, client, user provisioning |

### Monitoring Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| `mailpit` | `axllent/mailpit:latest` | `8025:8025`, `1025:1025` | Email testing (SMTP catch-all with web UI) |

### Networks

| Network | Purpose |
|---------|---------|
| `app-network` | Application services communication |
| `keycloak-network` | Keycloak and PostgreSQL isolation |

Both use the `bridge` driver.

### Volumes

| Volume | Purpose |
|--------|---------|
| `mariadb_data` | Persistent database storage |
| `redis_data` | Persistent Redis data |
| `minio_data` | Persistent object storage |
| `keycloak_db_data` | Persistent Keycloak database |
| `composer_cache` | Shared Composer cache between builds |

## Entrypoint Script

The `entrypoint.sh` script (126 lines) orchestrates container startup:

### Execution Sequence

1. **Dependency Detection**: Checks `/var/www/html/vendor` existence.
2. **Ownership Fix**: `chown -R www-data:www-data storage bootstrap/cache`.
3. **Database Wait**: Loops with `php artisan db:monitor` until MariaDB accepts connections (60 second timeout).
4. **Composer Install**: Runs `composer install --no-interaction` on first boot.
5. **NPM Install**: Runs `npm install` on first boot.
6. **Vite Build**: Runs `npm run build` for initial asset compilation.
7. **Application Key**: Generates `APP_KEY` if not already set.
8. **Reverb Credentials**: Generates `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` if missing, writes them to `.env`.
9. **Migrations**: Runs `php artisan migrate --force`.
10. **Seeding**: Runs `php artisan db:seed --force` (only on fresh databases).
11. **Link Storage**: Creates the `public/storage` symlink.
12. **Scribe Documentation**: Generates API docs via `php artisan scribe:generate`.
13. **Cron Start**: Starts the cron daemon.
14. **Supervisor Start**: Launches Supervisor with the configured programs.

### Error Handling

The script checks exit codes at critical stages and prints descriptive error messages. The database wait loop prevents premature migration failures.

## Keycloak Provisioning

The `keycloak-setup` Docker Compose service runs `docker/keycloak-setup.sh` automatically on `docker compose up -d`. It provisions Keycloak and writes the generated client secret back to `.env` so the application starts with the correct credentials.

### Service Configuration

The `keycloak-setup` service:

- Uses the `curlimages/curl:latest` image (lightweight, BusyBox-based).
- Runs as `user: "0:0"` (root) to allow writing to the bind-mounted `.env` file.
- Depends on `keycloak: service_healthy` — it only starts after Keycloak passes its health check.
- Uses `restart: "no"` — runs once and exits.

The `app` service depends on `keycloak-setup: service_completed_successfully`, ensuring the application only starts after provisioning is complete and `.env` has been updated.

### Using an External Keycloak Server

When Keycloak runs outside the Docker Compose network (e.g. on the host machine or a remote server), additional configuration is required:

1. **`extra_hosts`**: The `app` service includes `extra_hosts: ["host.docker.internal:host-gateway"]` so the container can resolve `host.docker.internal` to the Docker host's IP address (required on Linux; macOS/Windows resolve this automatically).

2. **`KEYCLOAK_INTERNAL_BASE_URL`**: In `docker-compose.dev.yml`, the env var is set to `${KEYCLOAK_INTERNAL_BASE_URL:-${KEYCLOAK_BASE_URL}}`, reading from `.env` instead of hardcoding a Docker-internal hostname. For external Keycloak, set this to `https://host.docker.internal` in `.env`.

3. **`CURLOPT_RESOLVE` approach**: The custom `KeycloakProvider` uses `CURLOPT_RESOLVE` to map the public hostname (e.g. `localhost`) to the internal IP address. This ensures the HTTP request uses the correct SNI, `Host` header, and SSL certificate while connecting to the right IP. This avoids issues with reverse proxies that reject requests with mismatched `Host` headers or HTTP/2 protocol errors.

4. **Keycloak Admin Console settings**: When using an external Keycloak, configure the client manually:
   - **Valid redirect URIs**: `http://wevetel.test:8002/auth/keycloak/callback` and `http://wevetel.test:8002/auth/keycloak/link/callback`
   - **Valid post logout redirect URIs**: `http://wevetel.test:8002/*`
   - **Backchannel logout URL**: Leave empty if Keycloak cannot reach the app (otherwise logout will be slow due to connection timeouts)
   - **User registration**: Enable in Realm Settings → Login tab for the registration flow to work

### Execution Sequence

1. **Admin Token**: Obtains an admin access token from the Keycloak master realm.
2. **Realm Creation**: Creates the realm specified by `KEYCLOAK_REALM` (default: `wevetel`) with display name "Wevetel".
3. **Realm Settings**: Enables user self-registration, password reset, and "Remember Me".
4. **Client Creation**: Creates a confidential OAuth client specified by `KEYCLOAK_CLIENT_ID` (default: `laravel`) with:
   - Redirect URIs for both central domain and wildcard subdomain patterns (e.g., `http://wevetel.test:8002/auth/keycloak/callback` and `http://*.wevetel.test:8002/auth/keycloak/callback`).
   - Web origins for CORS.
   - Post-logout redirect URIs with wildcard subdomain support.
   - Back-channel logout URL pointing to the internal Nginx service (`http://nginx:80/auth/keycloak/backchannel-logout`).
5. **Client Secret Retrieval**: Fetches the generated client secret from the Keycloak API.
6. **Auto-update `.env`**: Writes `KEYCLOAK_CLIENT_ID` and `KEYCLOAK_CLIENT_SECRET` to the mounted `.env` file. If the key already exists it is replaced in-place; if missing it is appended.

### Startup Order

The dependency chain ensures correct sequencing:

```
keycloak-db (healthy) → keycloak (healthy) → keycloak-setup (completed) → app → nginx
```

Because `KEYCLOAK_REALM`, `KEYCLOAK_CLIENT_ID`, and `KEYCLOAK_CLIENT_SECRET` are **not** set in the `app` service's `environment:` block, the application reads them from the mounted `.env` file at runtime. This means the secret written by `keycloak-setup` is immediately available when the app starts.

### Idempotency

The script is idempotent — if the realm or client already exists (HTTP 409), it skips creation and continues. This allows `docker compose up -d` to be run multiple times without errors.

### BusyBox Compatibility

Since `curlimages/curl` uses BusyBox (not GNU coreutils), the script avoids GNU-specific flags:

- Uses `sed -n 's|...|...|p'` instead of `grep -oP` (Perl regex not available in BusyBox).
- Uses `mktemp` + `sed > tmpfile` + `cat tmpfile > .env` instead of `sed -i`, because `sed -i` on a Docker bind-mounted file fails with "Resource busy" (it tries to rename a temp file over the mount point).

## MinIO Provisioning

The `createbuckets` service runs once on startup:

1. Configures MinIO client with admin credentials.
2. Creates the `wevetel` bucket.
3. Sets the bucket policy to `public`.

## First-Time Setup

### Prerequisites

- Docker Desktop or Docker Engine with Compose V2.
- Ports 3306, 5173, 5432, 6379, 8025, 8080, 8888, 9000, 9001 available.

### Steps

```bash
# Clone the repository
git clone <repository-url>
cd wevetel-starter-laravel

# Copy environment file
cp .env.example .env

# Start all services
docker compose -f docker-compose.dev.yml up -d --build

# Watch logs during first boot (entrypoint runs migrations, seeding, etc.)
docker compose -f docker-compose.dev.yml logs -f app
```

The first boot takes several minutes as it installs Composer and NPM dependencies, runs migrations, seeds the database, and generates API documentation.

### Accessing Services

| Service | URL |
|---------|-----|
| Application | `http://localhost:8888` |
| Vite HMR | `http://localhost:5173` (proxied via Nginx) |
| Keycloak Admin | `http://localhost:8080/admin` (`admin`/`admin`) |
| Mailpit | `http://localhost:8025` |
| MinIO Console | `http://localhost:9001` (`minioadmin`/`minioadmin`) |
| Horizon Dashboard | `http://localhost:8888/horizon` |
| Telescope Dashboard | `http://localhost:8888/telescope` |
| API Documentation | `http://localhost:8888/docs` |

### Default Credentials

| Service | Username | Password |
|---------|----------|----------|
| Application | `admin@admin.com` | `password` |
| Keycloak Admin | `admin` | `admin` |
| Keycloak Test User | `testuser` | `password` |
| MariaDB | `sail` | `password` |
| MinIO | `minioadmin` | `minioadmin` |

## Environment Variables

Key Docker-specific environment variables in `.env.example`:

### Database

```env
DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_DATABASE=wevetel_starter
DB_USERNAME=sail
DB_PASSWORD=password
```

### Redis

```env
REDIS_HOST=redis
```

### MinIO/S3

```env
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/wevetel
AWS_BUCKET=wevetel
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Reverb

```env
REVERB_HOST=app
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8888
```

### Keycloak

```env
KEYCLOAK_BASE_URL=http://keycloak:8080
KEYCLOAK_REALM=wevetel
KEYCLOAK_CLIENT_ID=wevetel-app
KEYCLOAK_CLIENT_SECRET=  # Auto-filled by keycloak-setup
KEYCLOAK_REDIRECT_URI=http://localhost:8888/auth/keycloak/callback
```

### Mail

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## Common Docker Commands

```bash
# Start services
docker compose -f docker-compose.dev.yml up -d

# Stop services
docker compose -f docker-compose.dev.yml down

# Rebuild after Dockerfile changes
docker compose -f docker-compose.dev.yml up -d --build

# View application logs
docker compose -f docker-compose.dev.yml logs -f app

# Run Artisan commands
docker compose -f docker-compose.dev.yml exec app php artisan <command>

# Run tests
docker compose -f docker-compose.dev.yml exec app php artisan test --compact

# Run Pint formatter
docker compose -f docker-compose.dev.yml exec app vendor/bin/pint

# Access container shell
docker compose -f docker-compose.dev.yml exec app bash

# Reset database
docker compose -f docker-compose.dev.yml exec app php artisan migrate:fresh --seed

# Restart Horizon
docker compose -f docker-compose.dev.yml exec app php artisan horizon:terminate

# Clear all caches
docker compose -f docker-compose.dev.yml exec app php artisan optimize:clear
```

## Troubleshooting

### Port Conflicts

If a port is already in use, modify the host port in `docker-compose.dev.yml`. Update corresponding `VITE_*` environment variables if changing Vite or Reverb ports.

### Database Connection Refused

The entrypoint script waits up to 60 seconds for MariaDB. If the timeout is reached, restart the `app` container after MariaDB is healthy.

### Keycloak Client Secret Mismatch

The `keycloak-setup` container provisions the client secret automatically and writes it to `.env`. If SSO fails with "Could not authenticate" or a 401 error, check the setup logs:

```bash
docker compose -f docker-compose.dev.yml logs keycloak-setup
```

Verify the `.env` secret matches what Keycloak generated:

```bash
# Check the secret in .env
grep KEYCLOAK_CLIENT_SECRET .env

# Compare with what's actually in Keycloak
docker compose -f docker-compose.dev.yml exec app php artisan tinker --execute="echo config('services.keycloak.client_secret');"
```

If they don't match, clear the config cache and restart the app:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan optimize:clear
docker compose -f docker-compose.dev.yml restart app nginx
```

For a completely clean start (wipes all data including Keycloak realm):

```bash
docker compose -f docker-compose.dev.yml down -v
docker compose -f docker-compose.dev.yml up -d
```

### Vite HMR Not Working

Verify the `vite` container is running and that Nginx proxy rules are active. Check `VITE_HMR_HOST` and `VITE_HMR_PORT` in `.env`.
