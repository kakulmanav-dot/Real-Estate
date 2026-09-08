# API Documentation

Base URL: `{APP_URL}/api/v1` (health check only is at `{APP_URL}/api/health`).

## Conventions

**Authentication**: Bearer token via `Authorization: Bearer <token>` header,
issued by `/auth/login` or `/auth/register`. No CSRF token is required.

**Success envelope**:
```json
{ "success": true, "message": "...", "data": { ... }, "meta": { ... } }
```
`meta` is present only on paginated list endpoints (`current_page`,
`per_page`, `total`, `last_page`).

**Error envelope**:
```json
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```
`errors` is present only on `422` validation responses.

**Status codes used**: `200` OK, `201` Created, `401` Unauthenticated,
`403` Forbidden, `404` Not Found, `422` Validation Error, `429` Too Many
Requests, `500` Server Error (generic message only outside local/testing).

---

## Public endpoints

### `GET /health`
Health check. No auth. Returns `200` with `{"status":"ok"}` or `503` with
`{"status":"degraded"}` if the database is unreachable. No sensitive details
exposed.

### `GET /v1/home`
Consolidated homepage payload: site settings, up to 6 featured published
properties, and approved testimonials, in one call.
- Auth: none
- Response: `data.settings`, `data.featured_properties[]`, `data.testimonials[]`

### `GET /v1/settings`
Public site settings (company info, hero copy, stats, social links, SEO).
- Auth: none

### `GET /v1/testimonials`
Approved testimonials, ordered by `sort_order`.
- Auth: none

### `GET /v1/properties`
Paginated, filterable property listing. Only `published` properties with a
non-null `published_at` are ever returned.
- Auth: none
- Query params: `search`, `purpose` (`sale`\|`rent`), `property_type`, `city`,
  `bedrooms`, `min_price`, `max_price`, `min_area`, `max_area`,
  `sort` (`newest`\|`price_asc`\|`price_desc`), `per_page` (max 50), `page`
- Errors: `422` if `max_price < min_price` or `max_area < min_area`, or an
  invalid `purpose`/`sort` value

### `GET /v1/properties/featured`
Up to 6 featured, published properties.
- Auth: none

### `GET /v1/properties/{slug}`
Single published property by slug, with images and creator.
- Auth: none
- `404` if the slug doesn't exist, is a draft/archived/sold property, or is
  soft-deleted

### `GET /v1/properties/{slug}/similar`
Up to 4 published properties sharing city or property type.
- Auth: none

### `POST /v1/enquiries`
Submit a general or property-specific enquiry. Rate-limited to 10/minute per
IP. Always persists (even if mail delivery fails) unless the honeypot
`website` field is filled, in which case the request is silently accepted
(`201`) but discarded without being saved or emailed.
- Auth: none (optional — if a Bearer token is present, the enquiry is linked
  to that user)
- Body: `name*`, `email*`, `message*`, `property_id` (nullable, must exist),
  `phone`, `subject`, `source`, `website` (honeypot — leave empty)
- Response: `201` with no meaningful `data`

---

## Authentication endpoints

Auth routes (`register`, `login`, `forgot-password`, `reset-password`) are
rate-limited to 5/minute per IP.

### `POST /v1/auth/register`
- Body: `name*`, `email*` (unique), `password*` (confirmed, min 8, letters+numbers), `phone`
- `role` is always forced to `user` regardless of request body
- Response: `201` with `data.user`, `data.token`

### `POST /v1/auth/login`
- Body: `email*`, `password*`
- `422` on wrong credentials, `403` if the account is deactivated
- Response: `200` with `data.user`, `data.token`

### `POST /v1/auth/logout`
- Auth: Bearer token required
- Revokes the current access token only

### `GET /v1/auth/me`
- Auth: Bearer token required
- Returns the authenticated user (never includes password hash)

### `POST /v1/auth/forgot-password`
- Body: `email*`
- Always returns the same `200` generic response regardless of whether the
  email exists, to prevent account enumeration

### `POST /v1/auth/reset-password`
- Body: `token*`, `email*`, `password*` (confirmed)
- `422` if the token is invalid or expired
- Revokes all of the user's existing tokens on success

### `PUT /v1/profile`
- Auth: Bearer token required
- Body (multipart if uploading an avatar): `name`, `email` (unique, excluding self), `phone`, `avatar` (image, max 2MB)
- `role`/`is_active` in the body are silently ignored (not mass-assignable here)

### `PUT /v1/profile/password`
- Auth: Bearer token required
- Body: `current_password*`, `password*` (confirmed)
- Revokes all other active tokens on success (current session stays valid)

---

## Favorites (authenticated users)

### `GET /v1/favorites`
Paginated list of the current user's saved (published) properties.

### `POST /v1/favorites/{property:slug}`
Save a property. Idempotent — saving an already-saved property returns `201`
without creating a duplicate row (enforced by a unique DB constraint).
`404` if the property is not published.

### `DELETE /v1/favorites/{property:slug}`
Remove a property from the current user's saved list.

---

## Admin endpoints

All routes below require `Authorization: Bearer <token>` **and** the
authenticated user to have `role = admin` and `is_active = true` (enforced by
the `admin` middleware) — otherwise `401` (no token) or `403` (wrong role/inactive).

### Dashboard
`GET /v1/admin/dashboard` — stats (total/published/draft/featured/sold/rented
properties, total/new enquiries, total users) plus the 5 most recent
properties and enquiries.

### Properties
| Method | Endpoint | Notes |
|---|---|---|
| GET | `/v1/admin/properties` | All properties incl. drafts and soft-deleted; `search`, `status`, and the same filters as the public listing |
| POST | `/v1/admin/properties` | Multipart; accepts `images[]` alongside property fields; `title*`, `description*`, `purpose*`, `property_type*`, `price*`, `address*`, `city*`, `bedrooms*`, `bathrooms*`, `area*` required; slug/reference_number auto-generated |
| GET | `/v1/admin/properties/{id}` | Single property by numeric ID (not slug) |
| PUT | `/v1/admin/properties/{id}` | Partial update; wrapped in a DB transaction — an invalid `status` value fails validation before any write |
| DELETE | `/v1/admin/properties/{id}` | Soft delete |
| POST | `/v1/admin/properties/{id}/restore` | Restore a soft-deleted property |
| DELETE | `/v1/admin/properties/{id}/force` | Permanently delete + remove its stored images |
| PATCH | `/v1/admin/properties/{id}/status` | Body: `status*` (`draft`\|`published`\|`sold`\|`rented`\|`archived`); sets/clears `published_at` accordingly |
| PATCH | `/v1/admin/properties/{id}/featured` | Body: `featured*` (boolean) |

### Property images
| Method | Endpoint | Notes |
|---|---|---|
| POST | `/v1/admin/properties/{id}/images` | Multipart `images[]`, each validated as `image`, `mimes:jpg,jpeg,png,webp`, max 5MB. First upload for a property becomes the cover automatically. Filenames are UUID-generated, never derived from client input. |
| PUT | `/v1/admin/properties/{id}/images/{image}` | Body: `alt_text` |
| PATCH | `/v1/admin/properties/{id}/images/{image}/cover` | Sets this image as the sole cover image |
| POST | `/v1/admin/properties/{id}/images/reorder` | Body: `order*` — array of image IDs in desired order |
| DELETE | `/v1/admin/properties/{id}/images/{image}` | Deletes the DB row and the stored file |

### Enquiries
| Method | Endpoint | Notes |
|---|---|---|
| GET | `/v1/admin/enquiries` | `search`, `status`, `property_id` filters |
| GET | `/v1/admin/enquiries/{id}` | |
| PATCH | `/v1/admin/enquiries/{id}` | Body: any of `status`, `assigned_to`, `admin_notes` |
| DELETE | `/v1/admin/enquiries/{id}` | |
| GET | `/v1/admin/enquiries/export` | Streams a CSV; values starting with `=`, `+`, `-`, `@` are prefixed with `'` to prevent spreadsheet formula injection |

### Testimonials
| Method | Endpoint | Notes |
|---|---|---|
| GET | `/v1/admin/testimonials` | Paginated, all (approved and unapproved) |
| POST | `/v1/admin/testimonials` | Multipart; `name*`, `text*`, `rating*` (1–5), `designation`, `image` |
| PUT | `/v1/admin/testimonials/{id}` | Same fields, all optional; send `_method=PUT` over POST when replacing the image (PHP cannot parse multipart PUT bodies) |
| DELETE | `/v1/admin/testimonials/{id}` | |
| PATCH | `/v1/admin/testimonials/{id}/approval` | Toggles `is_approved` |
| POST | `/v1/admin/testimonials/reorder` | Body: `order*` — array of testimonial IDs |

### Users
| Method | Endpoint | Notes |
|---|---|---|
| GET | `/v1/admin/users` | `search`, `role` filters |
| GET | `/v1/admin/users/{id}` | |
| PATCH | `/v1/admin/users/{id}` | Body: `role` and/or `is_active`. Returns `422` if the change would demote/deactivate the last remaining active admin. |

### Settings
| Method | Endpoint | Notes |
|---|---|---|
| GET | `/v1/admin/settings` | Same shape as the public endpoint |
| PUT | `/v1/admin/settings` | Body: any whitelisted setting key (see `Setting::defaults()`); unknown keys are silently ignored, not stored |

### Activity logs
`GET /v1/admin/activity-logs` — paginated list of administrative actions
(`actor`, `action`, `entity_type`, `entity_id`, `metadata`, `ip_address`,
`created_at`), newest first.

---

## Rate limits

| Endpoint group | Limit |
|---|---|
| `POST /v1/enquiries` | 10 requests/minute per IP |
| `POST /v1/auth/register`, `/login`, `/forgot-password`, `/reset-password` | 5 requests/minute per IP |
| All other routes | Laravel's default `api` throttling |

Exceeding a limit returns `429` with the standard error envelope.
