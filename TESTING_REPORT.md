# Testing Report

Date: 2026-09-08
Repository: `kakulmanav-dot/Real-Estate`, branch `feature/complete-laravel-backend`
Starting commit: `3b21760d7ece61e8194dba7950bf49dd440c2a57`

## Environment used

- **Backend automated tests**: PHPUnit against SQLite in-memory
  (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), `APP_ENV=testing`,
  `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`,
  `SESSION_DRIVER=array` — all configured in `backend/phpunit.xml`.
- **Migration/seeder verification**: real MySQL 8 (MariaDB 10.4 via local
  XAMPP), against a database explicitly named
  `realestate_migration_scratch_test` (and a second
  `realestate_migration_scratch_test2` for the no-admin-env-vars case), both
  dropped at the end of the session. Never run against the pre-existing
  `realestate`/`realestate_testing` databases belonging to a different,
  unrelated project on the same MySQL server.
- **Frontend automated tests**: Vitest + jsdom + React Testing Library, Node
  v24.6.0.
- **Manual/local smoke environment**: `backend/.env` pointed at a MySQL
  database named `real_estate` (matching `.env.example`'s default),
  migrated and seeded. This `.env` is untracked (gitignored) and was not
  committed.
- **End-to-end (Playwright)**: not executed — see blocker below.

## Integration smoke test (manual)

With `php artisan serve` running against the seeded `real_estate` MySQL
database, verified via raw `curl` (deliberately *not* using PHPUnit's
`getJson()` helper, to exercise exactly what a real HTTP client sends):
`GET /api/health`, `GET /api/v1/properties`, `POST /api/v1/auth/register`,
`POST /api/v1/auth/login` as the seeded admin, `GET /api/v1/admin/dashboard`
with that token, `POST /api/v1/enquiries`, and an unauthenticated
`GET /api/v1/favorites`. All returned the expected response **except** the
last one, which is exactly how bug #1 below was found — it only surfaces
when the client doesn't send an `Accept: application/json` header, which
every PHPUnit test helper does automatically. Re-ran the same `curl` checks
after the fix; all passed, including the previously-broken one and the
equivalent 403 case (non-admin token hitting an admin route without an
`Accept` header).

## Backend test files created

All under `backend/tests/Feature/`:
- `AuthenticationTest.php` — 22 tests
- `AuthorizationTest.php` — 11 tests
- `PublicPropertyTest.php` — 24 tests
- `AdminPropertyTest.php` — 18 tests
- `PropertyImageTest.php` — 13 tests
- `EnquiryTest.php` — 20 tests
- `FavoriteTest.php` — 8 tests
- `TestimonialTest.php` — 9 tests
- `SettingsHomepageTest.php` — 10 tests
- `DashboardUsersActivityTest.php` — 9 tests
- `ApiSecurityTest.php` — 19 tests

Also added: `tests/Concerns/CreatesUsers.php` (shared auth helper trait),
`database/factories/TestimonialFactory.php`, `ActivityLogFactory.php`, and
extended `EnquiryFactory`/`PropertyFactory`/`UserFactory` where needed.

The stock `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`
were removed (they only asserted `true === true` / that `/` returns 200 and
added no coverage).

## Frontend test files created

- `src/api/client.test.js` — API client base URL + error normalization
- `src/context/AuthContext.test.jsx` — session restore/clear behavior
- `src/routes/ProtectedRoutes.test.jsx` — `ProtectedRoute` + `AdminRoute` guards
- `src/pages/LoginPage.test.jsx`
- `src/pages/RegisterPage.test.jsx`
- `src/pages/HomePage.test.jsx` — data render, loading, and failure states
- `src/pages/NotFoundPage.test.jsx`
- `src/components/PropertyCard.test.jsx`
- `src/components/ui/Pagination.test.jsx`

Also added `frontend/src/test/setup.js` (jest-dom matchers, cleanup, an
`IntersectionObserver` mock for framer-motion's `whileInView`), and a `test`
script (`vitest run`) in `package.json`.

**Not covered by an automated frontend test** (deferred — see below):
`PropertiesPage` filter/search/URL-param behavior, `PropertyDetailsPage`,
`SavedPropertiesPage`, the homepage contact form and property enquiry form,
and every admin-dashboard page (properties form, image upload/preview,
enquiries management, testimonials, users, settings, activity logs). These
were exercised indirectly via the backend feature tests (which cover the API
contracts these pages depend on) and via the manual smoke checks below, but
not with dedicated component tests, given the scope of this session.

## End-to-end tests created

**None.** Blocker: `npx playwright install chromium` failed —
`Request to https://cdn.playwright.dev/builds/cft/153.0.8010.12/win64/chrome-win64.zip
timed out after 30000ms` on every retry. This sandboxed environment has no
outbound access to `cdn.playwright.dev`, so no browser binary could be
installed. Playwright itself (the npm package) installs fine; only the
browser binary download is blocked. No E2E test files or Playwright config
were added, per instructions not to leave a non-functional half-setup behind.

As a partial substitute, the flows E2E would have covered were verified via:
(a) the backend feature test suite exercising the real API end-to-end
(HTTP → controller → policy/validation → DB → response), and (b) manual
`curl`/browser smoke checks against a locally running `php artisan serve` +
`npm run dev` pair (registration, login, admin dashboard access, property
listing) during the initial build session.

## Total counts

| Suite | Tests | Assertions | Result |
|---|---|---|---|
| Backend (`php artisan test`) | 163 | 396 | **163 passed, 0 failed, 0 skipped** |
| Frontend (`npm test`) | 32 | — | **32 passed, 0 failed, 0 skipped** |
| End-to-end | 0 | — | Not run (environment blocker above) |

Exact command output (backend, final run):
```
Tests:    163 passed (396 assertions)
Duration: 5.11s
```
Exact command output (frontend, final run):
```
Test Files  9 passed (9)
     Tests  32 passed (32)
```

## Bugs discovered and fixed

1. **Guest/unauthenticated requests without an explicit `Accept: application/json`
   header crashed with a 500 instead of returning a 401** (`bootstrap/app.php`).
   Found via manual `curl` smoke testing against a real running server — every
   automated PHPUnit test used Laravel's `getJson()`/`postJson()` helpers,
   which *always* set that header, so this was invisible to the whole
   automated suite until it was checked manually. Laravel's default
   `Authenticate` middleware calls `route('login')` to build a redirect target
   whenever a request doesn't "expect JSON," but this is a pure API with no
   named `login` web route, so that call itself threw
   `RouteNotFoundException` — a 500 — before the intended 401 JSON response
   was ever produced. Any real HTTP client, load balancer health check, or
   proxy that doesn't set an `Accept` header would have hit this. **Fix**:
   `$middleware->redirectGuestsTo(fn () => null)` in `bootstrap/app.php`, so
   guests get a plain `AuthenticationException` (handled by the existing
   custom 401 JSON renderer) instead of a broken redirect attempt. Verified
   by hand with `curl -i` (no `Accept` header) before and after the fix, and
   captured as a permanent regression test:
   `test_guest_receives_json_401_even_without_an_accept_header`.

2. **Email enumeration via forgot-password** (`AuthController::forgotPassword`).
   The endpoint returned `422` for an unregistered email and `200` for a
   registered one, letting an attacker enumerate valid accounts. **Fix**:
   always return the same `200` generic response regardless of whether the
   account exists. Regression tests: `test_forgot_password_returns_generic_response_for_unknown_email`,
   `test_forgot_password_returns_identical_response_for_known_email`.

3. **Honeypot anti-spam bypass detectable by bots** (`StoreEnquiryRequest`).
   The honeypot field carried a `size:0` validation rule, so a bot filling it
   in got a distinguishing `422 {"website":["Submission rejected."]}` instead
   of the intended silent, success-shaped rejection — defeating the point of
   a honeypot and making the controller's silent-discard branch dead code.
   **Fix**: removed the rejecting validation rule; the controller's existing
   `if ($request->filled('website'))` branch now actually runs. Regression
   test: `test_honeypot_field_silently_rejects_submission_without_persisting`.

3. **No CSV formula-injection protection** (`Admin\EnquiryController::export`).
   A malicious enquiry name/subject like `=cmd|'/C calc'!A1` would be written
   verbatim into the exported CSV and could execute as a formula when opened
   in Excel/Sheets. **Fix**: added `csvSafe()`, prefixing any field value that
   starts with `=`, `+`, `-`, or `@` with a leading `'`. Regression test:
   `test_csv_export_neutralizes_formula_injection_payloads`.

5. **Draft properties could be favorited** (`FavoriteController::store`).
   Any authenticated user who knew (or guessed) a draft property's slug could
   favorite it, since the route model binding didn't check publication
   status. **Fix**: added an explicit `abort_unless(published)` check.
   Regression test: `test_draft_property_cannot_be_favorited`.

6. **Settings endpoint could leak arbitrary stored keys** (`Setting::allSettings()`).
   The method merged *every* row in the `settings` table into the public API
   response, not just the known/whitelisted keys — so any future setting
   stored under an unexpected key (e.g. an internal webhook secret) would
   have been exposed publicly with no code change needed to trigger it.
   **Fix**: `allSettings()` now only merges rows whose key is in
   `Setting::defaults()`. Regression test:
   `test_settings_endpoint_does_not_expose_unknown_stored_keys`.

7. **Form labels not associated with their inputs** (accessibility —
   `LoginPage`, `RegisterPage`, `ForgotPasswordPage`, `ResetPasswordPage`,
   `ProfilePage`). Every `<label>` lacked `htmlFor`, and every `<input>`
   lacked a matching `id`, so screen readers couldn't announce field labels
   and clicking a label didn't focus its input. Caught because
   `getByLabelText` (the correct, accessibility-respecting RTL query) failed
   until this was fixed. **Fix**: added matching `id`/`htmlFor` pairs across
   all five pages. **Not yet done**: the admin-dashboard forms
   (`AdminPropertyFormPage`, `AdminTestimonialsPage`, `AdminSettingsPage`,
   etc.) have the same pattern and were not swept in this session — flagged
   below as a remaining item.

8. **18 files with formatting deviations** (Laravel Pint) — import ordering,
   missing fully-qualified strict types, spacing. No behavioral change;
   fixed by running `./vendor/bin/pint`.

9. **13 frontend dependency vulnerabilities** (`npm audit`) — all in build
   tooling (`vite`, `rollup`, `postcss`, `esbuild`, `browserslist`, and
   transitive deps), none in code shipped to the browser. Fixed via
   `npm audit fix` (non-breaking; no `--force`). Verified lint/test/build all
   still pass afterward.

### Test-infrastructure issues found and fixed (not application bugs)

These didn't ship to users but are worth recording since they shaped the test
suite:
- `phpunit.xml` had no `APP_KEY`, causing every `Password::` broker call
  (forgot/reset password) to throw. Fixed by adding a dedicated test-only key.
- Laravel's `Illuminate\Auth\RequestGuard` caches the resolved user for the
  lifetime of the guard instance, which is reused across every simulated
  HTTP call within a single PHPUnit test method (not just within one real
  request). This made a logout-then-reuse-token test falsely pass initially
  (200 instead of the expected 401). Fixed in the test by calling
  `auth()->forgetGuards()` after logout — confirmed this is a test-harness
  artifact, not a real revocation bug, since real production requests each
  get a freshly booted guard.
- Plain `actingAs($user, 'sanctum')` doesn't populate `currentAccessToken()`;
  switched all authenticated test helpers to `Sanctum::actingAs()`.
- One N+1-query regression test initially miscounted because it flushed the
  query log *before* creating additional test fixtures instead of after,
  counting the fixtures' own INSERT queries as if they were request queries.
  Fixed the test; confirmed there is genuinely no N+1 (3 queries regardless
  of whether 3 or 13 properties exist).
- `UploadedFile::fake()->image()` requires the GD PHP extension, which this
  environment's PHP install doesn't have. Switched to
  `UploadedFile::fake()->create($name, $sizeKb, 'image/jpeg')`, which
  validates identically against the `image` MIME-type rule without needing
  real image bytes.

## Migration and seeder results

Run against `realestate_migration_scratch_test` (MySQL):

1. `php artisan migrate` from empty — all 11 migrations applied successfully.
2. `php artisan migrate:rollback` — all 11 rolled back cleanly; only the
   `migrations` bookkeeping table remained.
3. `php artisan migrate` again — reapplied successfully, identical result.
4. `php artisan db:seed` (first run) — `16 users, 16 properties, 6 property_images,
   3 testimonials, 17 settings, 10 enquiries`.
5. `php artisan db:seed` (second run) — **identical counts**: `16 users, 16
   properties, 6 property_images, 3 testimonials, 17 settings, 10 enquiries`.
   Confirmed idempotent (`AdminSeeder`/`SettingSeeder`/`TestimonialSeeder` use
   `updateOrCreate`/`firstOrCreate`; `PropertySeeder` checks for an existing
   slug before creating; `DevelopmentSeeder` checks row counts before adding more).
6. Admin creation from env vars: confirmed `admin@example.com` created with
   `role=admin`, `is_active=1`.
7. Admin seed behavior with vars absent: on a second scratch database with
   `ADMIN_EMAIL`/`ADMIN_PASSWORD` removed from `.env`, `AdminSeeder` printed
   `"ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping admin seeding."` and
   exited cleanly — no crash, no insecure default account created.
8. Foreign keys verified directly via `information_schema` — all 8 expected
   FKs present with the intended `ON DELETE` behavior:
   `activity_logs.user_id`→`users.id` (SET NULL),
   `enquiries.property_id`→`properties.id` (SET NULL),
   `enquiries.assigned_to`/`user_id`→`users.id` (SET NULL),
   `favorites.user_id`/`property_id`→`users`/`properties.id` (CASCADE),
   `properties.created_by`→`users.id` (CASCADE),
   `property_images.property_id`→`properties.id` (CASCADE).
9. Unique constraints verified: `favorites(user_id, property_id)`,
   `properties.slug`, `properties.reference_number`, `settings.key`,
   `users.email`.
10. Decimal precision verified: `properties.price DECIMAL(14,2)`,
    `properties.area DECIMAL(12,2)`, `latitude`/`longitude DECIMAL(10,7)` —
    never floats.
11. Soft deletes verified: `properties.deleted_at` column present and
    functions correctly (see `AdminPropertyTest::test_admin_can_soft_delete_and_restore_property`).
12. All of the above ran against real MySQL, not only SQLite — the automated
    PHPUnit suite runs on SQLite for speed/isolation, but this migration
    pass specifically validated MySQL compatibility.

Both scratch databases were dropped at the end of the session; no temporary
credentials remain in the repository.

## Authentication test results
22/22 passed — registration (success, validation, duplicate email, password
confirmation, role-escalation attempt blocked), login (success, invalid
credentials, inactive user, rate limiting), `/me` (authenticated/guest),
logout + token revocation, profile update (+ validation, + role-change
attempt blocked), change password (+ wrong current password), forgot/reset
password (generic response, valid reset, invalid token), and confirmation
that the user resource never serializes the password hash.

## Authorization test results
11/11 passed — guest/non-admin/admin access boundaries on authenticated and
admin routes, inactive-admin rejection, the property policy blocking
non-admin mutation attempts, favorite isolation between users, the
last-active-admin demotion/deactivation protections, and draft/soft-deleted
properties being invisible on public endpoints.

## Property and image test results
- Public property tests: 24/24 passed (listing, draft/archived/deleted
  hiding, slug resolution, featured/similar, pagination, all filter/search/
  sort combinations, validation errors, and a query-count regression test
  confirming no N+1 on the images relation).
- Admin property tests: 18/18 passed (CRUD, auto slug/reference generation
  with uniqueness on duplicate titles, publish/unpublish/sold/rented status
  transitions, featured toggle, soft delete/restore/force-delete, non-admin
  rejection, transactional update-failure behavior, and activity log
  creation for create/update/delete/restore).
- Property image tests: 13/13 passed (multi-upload, MIME validation,
  disguised-executable rejection, oversized-file rejection, default/explicit
  cover-image selection with only-one-cover enforcement, reordering, alt-text
  update, deletion of both the DB row and the stored file, unauthorized-user
  rejection, non-original-filename generation, and correct public URL
  formatting).

## Enquiry and CSV test results
20/20 passed — general and property-specific submission, validation, invalid
`property_id` rejection, the honeypot fix, rate limiting, IP/user-agent
capture, authenticated-user association, persistence despite a simulated
mail-transport exception, admin/sender mail dispatch assertions (via
`Mail::fake()`), admin listing/search/filter/status/assignment/notes/spam
workflows, deletion, CSV export content, the formula-injection fix, and
non-admin rejection of every admin enquiry endpoint.

## Favorite test results
8/8 passed — add, remove, list, duplicate prevention, the database-level
unique constraint (verified via a caught `QueryException`), guest rejection,
per-user isolation, and the draft-property fix.

## Admin test results
- Dashboard/users/activity: 9/9 passed — real-record-derived stats (verified
  exact counts, not just presence), recent lists, user search/role update,
  non-admin rejection, activity log creation and pagination, and
  admin-only access to logs.
- Testimonials: 9/9 passed — public approval filtering and ordering, admin
  CRUD, approval toggle, image upload/replacement, rating validation,
  non-admin rejection.
- Settings/homepage: 10/10 passed — public defaults, the consolidated
  homepage payload, unapproved-testimonial exclusion, admin retrieval/update,
  unknown-key rejection, email-format validation, cache invalidation on
  update, and the settings-leak fix.

## Security audit results

Searched the current branch's tracked files (not `vendor`/`node_modules`) for:
| Pattern | Result |
|---|---|
| `web3forms` / `access_key` | Not found in application code (only unrelated `AWS_ACCESS_KEY_ID` env references in Laravel's own default config files) |
| Passwords / API keys / private keys / DB credentials | None found hardcoded |
| `.env` files | None tracked; only `.env.example` files with placeholders |
| `dangerouslySetInnerHTML` | Not used anywhere in the frontend |
| Raw SQL (`DB::raw`/`DB::statement`/`DB::select`) | Not used anywhere — all queries go through Eloquent/query builder parameter binding |
| Unrestricted CORS | `allowed_origins` is derived from `FRONTEND_URL`, not `*`; verified with an automated test that an unapproved `Origin` header does not receive a matching `Access-Control-Allow-Origin` |
| Hardcoded production URLs | Only a `localhost:8000` **fallback default** in `api/client.js`, used solely when `VITE_API_BASE_URL` is unset |
| `ProjectUnderground` / `EXCHANGE` / `SchoolSync` / `Educity` / `AdmissionApplication` / `Program` / `Gallery` | None found anywhere in this repository |

### Web3Forms key — required external action

The historical Web3Forms access key (`c0d543a7-...`, redacted here) is
**absent from every file on this branch** but **remains visible in this
repository's git history**, in the pre-existing commits from before this
project's Laravel/React rewrite. Per the safety rules for this session, git
history was not rewritten and nothing was force-pushed. **You must manually
revoke or rotate this key in your Web3Forms account** — it should be treated
as publicly compromised regardless of code-level removal, since anyone with
read access to the repository's history (including public GitHub history if
this repo is or ever becomes public) can retrieve it.

## Composer and npm audit results

- `composer audit`: **No security vulnerability advisories found.**
- `npm audit` (before): 13 vulnerabilities (2 low, 2 moderate, 9 high) — all
  in dev/build tooling (`vite`, `rollup`, `postcss`, `esbuild`,
  `browserslist`, and their transitive dependencies), none in
  browser-shipped runtime code.
- `npm audit fix` (non-forced): resolved all 13. `npm audit` after: **found 0
  vulnerabilities**. Re-verified `npm run lint`, `npm test`, and
  `npm run build` all still pass after the dependency bump.

## Lint and production build results

- Backend: `./vendor/bin/pint --test` → `{"result":"passed"}` (after fixing
  the 18 files listed above). `php -l` clean on every PHP file. No duplicate
  `method+uri` route pairs among the 52 API routes.
- Frontend: `npm run lint` → 0 errors, 0 warnings. `npm run build` →
  succeeds; one pre-existing advisory (a >500KB JS chunk — not a new issue,
  not a lint/build failure) suggesting code-splitting as a future
  optimization, not fixed in this session since it's not a correctness bug.

## Documentation created

- `README.md` (root) — replaced the stock Vite template README
- `API_DOCUMENTATION.md`
- `backend/DEPLOYMENT.md`
- `frontend/VERCEL_DEPLOYMENT.md`
- `TESTING_REPORT.md` (this file)

## Remaining blockers or warnings

- **End-to-end tests**: blocked entirely — no network access to
  `cdn.playwright.dev` to download a browser binary in this environment. Can
  be completed in an environment with outbound internet access, or by using
  a pre-provisioned Playwright/browser Docker image.
- **Frontend component test coverage gap**: `PropertiesPage`,
  `PropertyDetailsPage`, `SavedPropertiesPage`, the two enquiry/contact
  forms, and all admin-dashboard pages have no dedicated Vitest coverage yet
  (see "Frontend test files created" above for the full list). Their
  underlying API contracts are covered by the backend suite, but their
  React-level behavior (loading states, form validation display, image
  preview, etc.) is not.
- **Admin-form accessibility**: the label/input association fix (bug #6
  above) was applied to the five public auth/profile pages but not swept
  across the admin dashboard forms.
- **Frontend bundle size**: the production JS bundle is ~522KB (161KB
  gzipped), triggering Vite's default >500KB chunk warning. Not a bug, but a
  candidate for `manualChunks`/dynamic `import()` code-splitting in a future
  pass.
- **Web3Forms key rotation**: see the dedicated section above — this is an
  action only you can take (external to this repository).

## Deferred to a later comprehensive testing session (per this session's scope)

- Full Playwright end-to-end suite (once the environment/browser blocker is
  resolved).
- Remaining frontend component tests listed above.
- Load/performance testing.
- Any additional manual cross-browser QA.
