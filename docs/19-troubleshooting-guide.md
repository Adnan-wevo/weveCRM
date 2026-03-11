# Troubleshooting Guide

## Overview

This document addresses common issues encountered during development with the Docker Compose environment. Each section describes the symptom, likely cause, and resolution.

## Docker and Container Issues

### Container Fails to Start

**Symptom**: The `app` container exits immediately or enters a restart loop.

**Possible Causes**:

1. **Port conflict**: Another process is using port 8888, 5173, 3306, or 6379.
   - Resolution: Run `ss -tlnp | grep <port>` to find the conflicting process. Either stop it or change the host port in `docker-compose.dev.yml`.

2. **Missing `.env` file**: The application requires a `.env` file.
   - Resolution: `cp .env.example .env`.

3. **Build cache stale**: Dockerfile changes not reflected.
   - Resolution: `docker compose -f docker-compose.dev.yml up -d --build --force-recreate`.

### Database Connection Refused

**Symptom**: `SQLSTATE[HY000] [2002] Connection refused` during migrations or application use.

**Cause**: The MariaDB container has not finished initializing.

**Resolution**: The entrypoint script waits up to 60 seconds. If the database is still not ready:

```bash
docker compose -f docker-compose.dev.yml logs mariadb
docker compose -f docker-compose.dev.yml restart app
```

### Entrypoint Script Hangs

**Symptom**: The `app` container logs show "Waiting for database connection..." indefinitely.

**Cause**: MariaDB container is unhealthy or credentials in `.env` do not match `docker-compose.dev.yml`.

**Resolution**: Verify that `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` match the MariaDB service environment variables in `docker-compose.dev.yml`.

### Permission Denied Errors

**Symptom**: `Permission denied` errors when writing to `storage/` or `bootstrap/cache/`.

**Resolution**:

```bash
docker compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker compose -f docker-compose.dev.yml exec app chmod -R 775 storage bootstrap/cache
```

## Frontend Issues

### Vite Manifest Not Found

**Symptom**: `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`.

**Cause**: Frontend assets have not been built.

**Resolution**:

```bash
# Inside the container
docker compose -f docker-compose.dev.yml exec app npm run build

# Or start the Vite dev server
docker compose -f docker-compose.dev.yml up -d vite
```

### Hot Module Replacement Not Working

**Symptom**: Changes to Blade, CSS, or JavaScript files do not reflect in the browser without a full page reload.

**Possible Causes**:

1. **Vite container not running**: Check `docker compose -f docker-compose.dev.yml ps vite`.
2. **HMR host mismatch**: Verify `VITE_HMR_HOST` and `VITE_HMR_PORT` in `.env`.
3. **Nginx proxy misconfigured**: The `/@vite/` location block must proxy to the Vite container.

**Resolution**: Restart the Vite container and verify browser console for WebSocket connection errors.

### Tailwind CSS Classes Not Applied

**Symptom**: Newly added Tailwind classes have no visual effect.

**Cause**: The class is not included in the Tailwind content scan paths.

**Resolution**: Verify that the file containing the class is within Tailwind's content configuration. For module views, ensure `Modules/*/resources/views/**/*.blade.php` is scanned. Restart the Vite dev server after config changes.

## Authentication Issues

### Keycloak SSO Login Fails

**Symptom**: Clicking "Login with SSO" produces an error or redirect loop.

**Possible Causes**:

1. **Keycloak not ready**: The `keycloak-setup` container may not have finished provisioning.
   - Resolution: `docker compose -f docker-compose.dev.yml logs keycloak-setup`

2. **Client secret missing**: The Keycloak client secret is not in `.env`.
   - Resolution: Check `KEYCLOAK_CLIENT_SECRET` in `.env`. The `keycloak-setup` container should output the generated secret. If missing:
     ```bash
     docker compose -f docker-compose.dev.yml logs keycloak-setup | grep "Client Secret"
     ```

3. **Redirect URI mismatch**: The callback URL does not match Keycloak's client configuration.
   - Resolution: Ensure `KEYCLOAK_REDIRECT_URI` in `.env` matches the URI configured in Keycloak's `wevetel-app` client settings.

### Two-Factor Authentication Code Rejected

**Symptom**: Valid TOTP codes from the authenticator app are rejected.

**Cause**: Clock skew between the server and the authenticator app.

**Resolution**: Ensure the Docker host and the authenticator device are synchronized via NTP.

### Magic Login Link Expired

**Symptom**: Clicking a magic login link shows "This link has expired."

**Resolution**: Request a new magic login link. Tokens are single-use and time-limited.

## Multi-Tenancy Issues

### Tenant Not Found

**Symptom**: Custom `tenant-not-found` error page displayed.

**Cause**: The tenant identifier in the URL (subdomain or path prefix) does not match any tenant in the database.

**Resolution**: Verify the tenant exists in the `tenants` table with the correct domain. Use the Organisation Setup module to manage tenants.

### Cross-Tenant Data Visibility

**Symptom**: Data from one tenant appears while browsing another tenant's context.

**Possible Causes**:

1. **Missing tenant scope**: A query is not using the tenant-scoped model or relationship.
2. **Cache not isolated**: Cache keys colliding between tenants.
3. **Session stale**: The session retains a previous tenant's context.

**Resolution**: Clear the session and cache. Verify that all queries go through tenant-scoped models. Check that `CacheTenancyBootstrapper` is active.

### Tenant Middleware Order

**Symptom**: Authorization checks fail or produce unexpected behavior.

**Cause**: Tenant identification middleware must run before permission-checking middleware.

**Resolution**: Verify middleware priority in `bootstrap/app.php`. The `InitializeTenancyByDomainOrSubdomain` and `InitializeTenancyByRequestData` middleware must be registered before `EnsureValidTenantSession`.

## Queue and Job Issues

### Jobs Not Processing

**Symptom**: Queued jobs (imports, exports) remain in pending state.

**Possible Causes**:

1. **Horizon not running**: Check Supervisor status.
   - Resolution: `docker compose -f docker-compose.dev.yml exec app supervisorctl status`
2. **Redis connection failed**: Verify Redis container is healthy.
3. **Queue connection misconfigured**: `QUEUE_CONNECTION` must be `redis`.

### Failed Jobs

**Symptom**: Jobs appear in the "Failed" section of the Horizon dashboard.

**Resolution**:

1. Check the exception message and stack trace in Horizon.
2. Fix the underlying issue.
3. Retry from Horizon dashboard or via CLI:
   ```bash
   php artisan queue:retry <job-id>
   ```

## Broadcasting Issues

### Real-Time Updates Not Received

**Symptom**: Creating or updating records in one browser tab does not refresh the table in another tab.

**Possible Causes**:

1. **Reverb not running**: Check Supervisor status for the `reverb` process.
2. **Echo not initialized**: Check browser console for Echo connection errors.
3. **Channel subscription failed**: Verify `VITE_REVERB_HOST` and `VITE_REVERB_PORT` in `.env`.

**Resolution**: Open browser developer tools, check the Network tab for WebSocket connections, and verify the Console tab for Echo-related errors.

## Email Issues

### Emails Not Received

**Symptom**: Password reset, magic login, or notification emails do not arrive.

**Cause**: Mail configuration is not pointing to Mailpit.

**Resolution**: Verify `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Check Mailpit at `http://localhost:8025` for captured emails.

## Storage Issues

### File Upload Fails

**Symptom**: Uploading files produces a 413 or PHP error.

**Possible Causes**:

1. **PHP limits**: `upload_max_filesize` and `post_max_size` are set to 50 MB.
2. **Nginx limits**: The `client_max_body_size` in Nginx config.

**Resolution**: For files larger than 50 MB, increase limits in `docker/php/php.ini` and `docker/nginx/default.conf`.

### MinIO Connection Refused

**Symptom**: File operations fail with connection errors to MinIO.

**Resolution**: Verify the MinIO container is running and credentials match:

```env
AWS_ENDPOINT=http://minio:9000
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
```

Check the MinIO console at `http://localhost:9001`.

## Debugging Tools

### Telescope

Access at `http://localhost:8888/telescope` to inspect:

- Recent requests and their queries.
- Slow queries and N+1 problems.
- Job execution details.
- Mail messages and notifications.

### Horizon

Access at `http://localhost:8888/horizon` to monitor:

- Queue throughput and job status.
- Failed jobs with retry functionality.
- Worker process status.

### Pail

Real-time log streaming:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan pail
```

### Container Logs

```bash
# All services
docker compose -f docker-compose.dev.yml logs -f

# Specific service
docker compose -f docker-compose.dev.yml logs -f app
docker compose -f docker-compose.dev.yml logs -f mariadb
docker compose -f docker-compose.dev.yml logs -f keycloak
```

## Resetting the Environment

To completely reset the development environment:

```bash
# Stop all containers and remove volumes
docker compose -f docker-compose.dev.yml down -v

# Remove built images
docker compose -f docker-compose.dev.yml down --rmi local

# Start fresh
docker compose -f docker-compose.dev.yml up -d --build
```

This destroys all persistent data (database, Redis, MinIO, Keycloak) and rebuilds from scratch.
