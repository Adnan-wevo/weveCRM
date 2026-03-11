#!/bin/bash
set -e

# ── Wait for MariaDB ──────────────────────────────────────────────────────────
echo "⏳ Waiting for database at ${DB_HOST}:${DB_PORT}..."
max_tries=30
count=0
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}',
            '${DB_PASSWORD}'
        );
        exit(0);
    } catch (\Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "❌ Database not available after ${max_tries} attempts. Exiting."
        exit 1
    fi
    echo "   waiting... (${count}/${max_tries})"
    sleep 2
done
echo "✅ Database ready"

cd /var/www/html

# ── Fix ownership so host user (UID 1000) can edit files created inside Docker ─
# 'php artisan make:*' and composer run as root inside the container, producing
# root-owned files that VS Code / the host user cannot save back.
# We fix this on every startup so it's always correct regardless of --build.
echo "🔑 Fixing file ownership..."
for _dir in app config database resources routes tests bootstrap stubs Modules public/build storage .github .claude; do
    chown -R 1000:1000 "/var/www/html/${_dir}" 2>/dev/null || true
done
chown 1000:1000 \
    /var/www/html/composer.json \
    /var/www/html/composer.lock \
    /var/www/html/package.json \
    /var/www/html/package-lock.json \
    /var/www/html/phpunit.xml \
    /var/www/html/pint.json \
    /var/www/html/vite.config.js \
    /var/www/html/artisan \
    /var/www/html/modules_statuses.json \
    /var/www/html/.env 2>/dev/null || true

# ── Fix storage & cache permissions (bind mount is owned by host user) ──────────
# chmod 777 so both host user and www-data (php-fpm) can write (dev only)
chmod -R 777 storage bootstrap/cache

# ── Ensure database/migrations is traversable by www-data (Horizon worker) ──
# Without o+x on directories, the queue worker cannot glob tenant migration
# files, causing tenant DB creation to succeed but migrations to silently skip.
find database/migrations -type d -exec chmod o+rx {} +

# ── Install Composer dependencies ─────────────────────────────────────────────
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader --no-progress

# ── Install Node dependencies ─────────────────────────────────────────────────
echo "📦 Installing Node dependencies..."
npm install --no-progress

# ── Build frontend assets ─────────────────────────────────────────────────────
echo "🏗️  Building frontend assets..."
npm run build

# ── Generate app key if missing ───────────────────────────────────────────────
php artisan key:generate --no-interaction --ansi 2>/dev/null || true

# ── Generate Reverb credentials if missing ────────────────────────────────────
if grep -qE '^REVERB_APP_ID=$' /var/www/html/.env 2>/dev/null; then
    echo "🔑 Generating Reverb credentials..."
    REVERB_ID=$(php -r "echo rand(100000, 999999);")
    REVERB_KEY=$(php -r "echo bin2hex(random_bytes(16));")
    REVERB_SECRET=$(php -r "echo bin2hex(random_bytes(16));")
    sed -i "s/^REVERB_APP_ID=$/REVERB_APP_ID=${REVERB_ID}/" /var/www/html/.env
    sed -i "s/^REVERB_APP_KEY=$/REVERB_APP_KEY=${REVERB_KEY}/" /var/www/html/.env
    sed -i "s/^REVERB_APP_SECRET=$/REVERB_APP_SECRET=${REVERB_SECRET}/" /var/www/html/.env
    echo "✅ Reverb credentials generated"
fi

# ── Terminate Horizon gracefully before migrate (picks up new code on restart) ─
echo "🛑 Terminating Horizon (will restart via supervisor)..."
php artisan horizon:terminate --wait --no-interaction 2>/dev/null || true

# ── Run migrations ────────────────────────────────────────────────────────────
echo "🗄️  Running migrations..."
php artisan migrate --force --no-interaction
# ── Run tenant migrations (bring existing tenant DBs up-to-date) ──────────
if [ "$(php artisan tinker --execute="echo config('tenancy.database.multi_db') ? '1' : '0';" 2>/dev/null)" = "1" ]; then
    echo "🗄️  Running tenant migrations..."
    php artisan tenants:migrate --no-interaction 2>/dev/null || true
fi
# ── Seed role/permissions if specified ───────────────────────────────────────
if [ "${DB_SEED:-false}" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force --no-interaction
fi

# ── Storage link ──────────────────────────────────────────────────────────────
php artisan storage:link --no-interaction 2>/dev/null || true

# ── Clear & warm caches ───────────────────────────────────────────────────────
php artisan optimize:clear

# ── Generate API documentation ────────────────────────────────────────────────
if [ -f "config/scribe.php" ]; then
    echo "📖 Generating API documentation..."
    php artisan scribe:generate --no-interaction 2>/dev/null || true
fi

# ── Start cron ────────────────────────────────────────────────────────────────
service cron start
echo "✅ Cron started"

# ── Artisan wrapper — runs as host user (uid 1000) ────────────────────────────
# `docker exec wevetel_app_dev artisan make:*` will create files owned by the
# host user instead of root, so VS Code can save them without permission errors.
cat > /usr/local/bin/artisan << 'WRAPPER'
#!/bin/bash
exec gosu 1000:1000 php /var/www/html/artisan "$@"
WRAPPER
chmod +x /usr/local/bin/artisan
echo "✅ Artisan wrapper created (runs as uid 1000)"

# ── Start supervisor (manages php-fpm + horizon + reverb) ────────────────────
echo "🚀 Starting supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
