#!/usr/bin/env bash
# Render start command for the Laravel API container.
#
# - set -e: abort on the first failing command instead of limping on.
# - exec at the end: replaces this shell with the PHP process (PID 1) so
#   Render's SIGTERM on deploy/restart reaches Laravel directly.
set -e

cd /var/www/html

echo "Clearing stale cached configuration/routes from the built image..."
# These only ever remove files that may not exist on a fresh image/disk
# (nothing was ever cached), so a "nothing to clear" failure must not abort boot.
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

echo "Running database migrations..."
php artisan migrate --force

echo "Running idempotent database seeders..."
php artisan db:seed --force

echo "Ensuring the public storage symlink exists..."
# storage:link fails if the target already exists (e.g. redeploy of the same
# ephemeral disk); that is not a startup failure, so don't let it abort boot.
php artisan storage:link || true

# config:cache is safe once real env vars are present in the container.
# route:cache is skipped when it can't be trusted: it fails hard on any
# Closure-based route (routes/web.php defines one), so a failed attempt here
# must not take down the app — it just means routes stay uncached this boot.
echo "Caching configuration..."
php artisan config:cache

echo "Attempting route cache (skipped safely if routes can't be cached)..."
php artisan route:cache || echo "Route cache skipped (closure-based routes present)."

PORT="${PORT:-10000}"
echo "Starting Laravel on 0.0.0.0:${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
