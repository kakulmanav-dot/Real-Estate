# Real Estate

A full-stack real estate listing platform: a React/Vite public site and admin
dashboard backed by a Laravel REST API. Visitors browse and search property
listings, save favorites, and submit enquiries; admins manage properties,
images, enquiries, testimonials, users, and site settings from a dedicated
dashboard.

## Architecture

```
Browser ──HTTPS──> React/Vite SPA (Vercel) ──HTTPS/JSON──> Laravel API (any PHP host)
                                                                  │
                                                            MySQL 8+
```

- The frontend is a static SPA (deployable to Vercel or any static host) that
  talks to the backend exclusively over the `/api/v1` REST API using a Bearer
  token issued by Laravel Sanctum — there is no server-rendered coupling
  between the two, so they can be deployed independently and on different
  domains.
- The backend is a standard Laravel application deployable to any PHP 8.2+
  host (shared hosting, a VPS, Railway, Render, etc.) with a MySQL database.

## Features

**Public site**
- Home page with featured properties, approved testimonials, and site
  settings pulled from a single consolidated API call
- Property listing with search, filters (purpose, type, city, bedrooms,
  price/area range), sorting, and pagination — all reflected in the URL
- Property detail page with an image gallery, amenities, facts, an enquiry
  form, and similar-property suggestions
- User registration/login, password reset, profile management
- Saved/favorite properties for authenticated users

**Admin dashboard** (`/admin`)
- Dashboard with live stats (published/draft/sold/rented counts, enquiries,
  users) and recent activity
- Property CRUD with multi-image upload, cover selection, reordering,
  publish/unpublish, feature toggle, soft delete/restore, permanent delete
- Enquiry management: search, filter, status/assignment/notes, spam marking,
  CSV export (with formula-injection protection)
- Testimonial management with approval workflow and image upload
- User management (role changes, activation) with last-admin protection
- Site settings editor
- Activity log of administrative actions

## Folder structure

```
/
├── backend/            Laravel 12 API (PHP 8.2+, MySQL)
│   ├── app/
│   │   ├── Enums/              Status/role enums
│   │   ├── Http/
│   │   │   ├── Controllers/    Api/V1 (public) and Api/V1/Admin (admin-gated)
│   │   │   ├── Requests/       Form Request validation classes
│   │   │   └── Resources/      API Resource transformers
│   │   ├── Mail/                Enquiry notification mailables
│   │   ├── Models/
│   │   ├── Policies/            Property authorization policy
│   │   └── Services/            PropertyService (transactional business logic)
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/api.php
│   └── tests/Feature/           162 backend feature tests
├── frontend/           React 19 + Vite SPA
│   └── src/
│       ├── api/                 Centralized API client + per-domain modules
│       ├── context/AuthContext.jsx
│       ├── routes/              ProtectedRoute / AdminRoute
│       ├── components/
│       │   └── admin/
│       └── pages/
│           └── admin/
├── API_DOCUMENTATION.md
├── backend/DEPLOYMENT.md
├── frontend/VERCEL_DEPLOYMENT.md
└── TESTING_REPORT.md
```

## Technical stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel 12, PHP 8.2+ |
| Auth | Laravel Sanctum (Bearer token) |
| Database | MySQL 8+ (SQLite in-memory for automated tests) |
| Queue | Database driver (`sync` in tests) |
| Frontend | React 19, Vite 7, React Router 7 |
| Styling | Tailwind CSS 4 |
| Animation | Framer Motion |
| HTTP client | axios |
| Frontend tests | Vitest, React Testing Library |
| Backend tests | PHPUnit |

## Requirements

- PHP 8.2+
- Composer 2
- MySQL 8+ (or MariaDB 10.4+)
- Node.js 20+ and npm

## Backend setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials (see [MySQL setup](#mysql-setup)
and [Environment variables](#environment-variables) below), then:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

The API is now available at `http://localhost:8000/api/v1`.

## Frontend setup

```bash
cd frontend
npm ci
cp .env.example .env.local   # or set VITE_API_BASE_URL directly
npm run dev
```

The site is now available at `http://localhost:5173`.

## MySQL setup

```sql
CREATE DATABASE real_estate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Point `DB_DATABASE` (and `DB_USERNAME`/`DB_PASSWORD`) in `backend/.env` at
this database.

## Environment variables

See `backend/.env.example` and `frontend/.env.example` for the full list with
safe placeholders. Key variables:

**Backend**
| Variable | Purpose |
|---|---|
| `DB_*` | MySQL connection |
| `FRONTEND_URL` | Used for CORS `allowed_origins` |
| `SANCTUM_STATEFUL_DOMAINS` | Reserved for cookie-based SPA auth (not required for the default Bearer-token flow) |
| `FILESYSTEM_DISK=public` | Where property/testimonial/avatar images are stored |
| `MAIL_*` | SMTP config — with `MAIL_MAILER=log` (the default), enquiry emails are still generated and enquiries are still saved, just logged instead of sent |
| `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Used once by `AdminSeeder` to create the first admin account. Never commit real values. |

**Frontend**
| Variable | Purpose |
|---|---|
| `VITE_API_BASE_URL` | Base URL of the backend API, e.g. `http://localhost:8000/api/v1` in development |

Never place secrets in `VITE_*` variables — anything prefixed `VITE_` is
compiled into the public browser bundle.

## Migrations and seeders

```bash
php artisan migrate            # apply schema
php artisan migrate:rollback   # roll back the last batch
php artisan db:seed            # admin (from env), settings, 6 seed properties, 3 testimonials
```

Seeders are idempotent — running `db:seed` repeatedly does not create
duplicate records. In `local`/`development` environments, an additional
`DevelopmentSeeder` adds sample users, properties, and enquiries via factories.

If `ADMIN_EMAIL`/`ADMIN_PASSWORD` are not set, `AdminSeeder` logs a warning
and skips admin creation rather than creating an account with a default
password.

## Storage setup

Property images, testimonial images, and user avatars are stored on the
`public` disk (`storage/app/public`) and served via the `storage:link` symlink:

```bash
php artisan storage:link
```

To use S3-compatible storage instead, set `FILESYSTEM_DISK=s3` and the
corresponding `AWS_*` variables in `.env` — no application code changes are
required since all uploads go through Laravel's `Storage` facade.

## Queue worker

Enquiry notification emails are queued (`QUEUE_CONNECTION=database` by
default). Run a worker in production:

```bash
php artisan queue:work
```

In local development without a running worker, queued jobs simply wait in the
`jobs` table until a worker processes them — enquiries are still saved
immediately regardless of queue/mail state.

## Mail configuration

Set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
and `MAIL_ENCRYPTION` for a real SMTP provider. With the default
`MAIL_MAILER=log`, outgoing mail is written to `storage/logs/laravel.log`
instead of being sent — enquiries are never lost or blocked by mail
configuration issues.

## Admin account creation

Set `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `backend/.env`, then:

```bash
php artisan db:seed --class=AdminSeeder
```

Re-running this command updates the existing admin's name/password rather
than creating a duplicate.

## Test commands

**Backend** (162 tests, 394 assertions — see `TESTING_REPORT.md` for full results):
```bash
cd backend
php artisan test
./vendor/bin/pint --test   # formatting check
composer audit             # dependency security audit
```

**Frontend** (32 tests):
```bash
cd frontend
npm test
npm run lint
npm run build
npm audit
```

## API base URL

All backend routes are namespaced under `/api/v1`, plus an unauthenticated
`GET /api/health` check. See `API_DOCUMENTATION.md` for the complete route
reference.

## Vercel frontend deployment

See `frontend/VERCEL_DEPLOYMENT.md` for full instructions. Summary:

- Root Directory: `frontend`
- Framework Preset: Vite
- Build Command: `npm run build`
- Output Directory: `dist`
- Environment variable: `VITE_API_BASE_URL=https://your-backend-domain.com/api/v1`
- `frontend/vercel.json` already rewrites all routes to `index.html` for
  client-side routing

## Laravel backend deployment overview

See `backend/DEPLOYMENT.md` for full instructions. Summary:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work   # run under a process supervisor (e.g. Supervisor)
```

Set `APP_ENV=production`, `APP_DEBUG=false`, and `FRONTEND_URL` /
`SANCTUM_STATEFUL_DOMAINS` to your real Vercel domain.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Frontend gets CORS errors | `FRONTEND_URL` in backend `.env` doesn't match the frontend's actual origin (protocol + host + port must match exactly) |
| 401 on every authenticated request | Missing/expired Bearer token; check the frontend is sending `Authorization: Bearer <token>` and that `localStorage.auth_token` is set |
| Images return 404 | `php artisan storage:link` was not run, or `APP_URL` doesn't match the domain the images are actually served from |
| Enquiry emails never arrive | Expected with `MAIL_MAILER=log` — check `storage/logs/laravel.log`; configure real SMTP credentials to send actual emails |
| `php artisan migrate` fails with "Unknown database" | The database in `DB_DATABASE` doesn't exist yet — create it first (see MySQL setup) |
| Seeder admin login fails | `ADMIN_EMAIL`/`ADMIN_PASSWORD` were unset when you ran the seeder, so no admin was created — set them and re-run `php artisan db:seed --class=AdminSeeder` |
| Direct navigation to a frontend route 404s on Vercel | `frontend/vercel.json` SPA rewrite is missing or Root Directory isn't set to `frontend` |

## Security notes

- Authentication is stateless Bearer-token (Laravel Sanctum personal access
  tokens), not cookie/session-based, so no CSRF token is required for API
  calls — but never expose a token outside of `localStorage` on the frontend
  origin.
- The historical Web3Forms API key that was previously used for the contact
  form **remains visible in this repository's git history** (pre-existing
  commits from before this rewrite). It has been fully removed from the
  current codebase, but since it was already public, treat it as compromised
  and revoke/rotate it from your Web3Forms account — this cannot be undone by
  rewriting history without coordinating a force-push, which this project
  deliberately avoids.
- CSV export of enquiries neutralizes spreadsheet formula injection
  (values starting with `=`, `+`, `-`, or `@` are prefixed with a quote).
- Property image uploads are validated by MIME type and size; filenames are
  never derived from user-supplied input.
- See `TESTING_REPORT.md` for the full list of security-relevant bugs found
  and fixed during the QA pass (email enumeration, honeypot bypass, and
  others).
