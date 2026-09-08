# Backend Deployment

Deployable to any PHP 8.2+ host with MySQL: a VPS (Hostinger, DigitalOcean),
Railway, Render, or shared hosting with SSH/Composer access. This app does
**not** deploy to Vercel — see `frontend/VERCEL_DEPLOYMENT.md` for the
frontend instead.

## Production environment variables

Copy `.env.example` to `.env` on the server and set real values:

```env
APP_NAME="Real Estate API"
APP_ENV=production
APP_KEY=                          # generate with php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.example.com

FRONTEND_URL=https://your-app.vercel.app
SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

FILESYSTEM_DISK=public
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

`APP_DEBUG` **must** be `false` in production — the global exception handler
in `bootstrap/app.php` only returns generic `500` messages (no stack traces,
no file paths, no SQL) when the app is not in debug mode.

## Deployment steps

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate            # only if APP_KEY is not already set
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then start the app server (php-fpm + nginx/Apache, or `php artisan serve`
behind a reverse proxy for simple setups) and run the queue worker under a
process supervisor so it restarts on crash/deploy:

```bash
php artisan queue:work --tries=3
```

A minimal Supervisor config:

```ini
[program:real-estate-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/real-estate/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
```

## First admin account

Set `ADMIN_NAME`/`ADMIN_EMAIL`/`ADMIN_PASSWORD` in the production `.env`
**before** running the seeder, then:

```bash
php artisan db:seed --class=AdminSeeder
```

This is idempotent — safe to re-run after rotating the password.

## CORS / Sanctum in production

- `FRONTEND_URL` drives `config/cors.php`'s `allowed_origins` — set it to
  your exact Vercel URL (protocol + host, no trailing slash, no wildcard).
- This app authenticates via Bearer tokens (Sanctum personal access tokens),
  not cookie-based SPA sessions, so `SANCTUM_STATEFUL_DOMAINS` and the
  `SESSION_*` cookie variables are not required for the API to function —
  they're included above only in case a future cookie-based flow is added.

## Health check

`GET https://api.example.com/api/health` — use this for uptime monitoring /
load balancer health checks. It reports `200`/`ok` or `503`/`degraded`
without leaking internal details.

## After every deploy

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart   # picks up new code in the queue worker
```

## Storage note

Uploaded images live on the local `public` disk by default
(`storage/app/public`), symlinked to `public/storage`. On a multi-server or
ephemeral-filesystem host (e.g. some PaaS providers), switch to S3-compatible
storage instead:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
```

No application code changes are required — all uploads already go through
Laravel's `Storage` facade.
