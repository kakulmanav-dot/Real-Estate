# Vercel Frontend Deployment

## Project settings

| Setting | Value |
|---|---|
| Root Directory | `frontend` |
| Framework Preset | Vite |
| Install Command | `npm ci` |
| Build Command | `npm run build` |
| Output Directory | `dist` |

## Environment variable

| Name | Value |
|---|---|
| `VITE_API_BASE_URL` | `https://your-backend-domain.com/api/v1` |

Set this in the Vercel project's Environment Variables settings — do not
hardcode a backend URL in source. Never put secrets in a `VITE_*` variable:
anything with that prefix is compiled into the public JS bundle and visible
to every visitor.

## SPA routing

`frontend/vercel.json` already rewrites every path to `index.html` so direct
navigation to (or a refresh on) client-side routes like `/properties/some-slug`
or `/admin/enquiries/5` works correctly instead of 404ing:

```json
{
  "rewrites": [{ "source": "/(.*)", "destination": "/index.html" }]
}
```

## Steps

1. Import the GitHub repository into Vercel.
2. Set Root Directory to `frontend` in the project's General settings.
3. Add the `VITE_API_BASE_URL` environment variable (for Production, and
   separately for Preview if you want previews to hit a staging API).
4. Deploy. Vercel auto-detects the Vite framework preset once Root Directory
   is set correctly.
5. Point your backend's `FRONTEND_URL` (and thus its CORS `allowed_origins`)
   at the resulting `https://your-project.vercel.app` domain — CORS errors
   after deployment almost always mean this value doesn't match exactly
   (protocol + host, no trailing slash).

## Custom domain

If you attach a custom domain in Vercel, update `FRONTEND_URL` on the
backend to match the custom domain too — the API only trusts the origin(s)
listed there.

## Verifying the deployment

- Load the deployed site's homepage and confirm featured properties/testimonials
  render (proves the frontend can reach the backend and CORS is correct).
- Open a property detail page directly by URL (not by clicking through) to
  confirm the SPA rewrite is working.
- Log in and confirm the admin dashboard loads for an admin account.
