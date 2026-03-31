# StripeDesk frontend (Vue 3 + Vite)

## Development (API on host)

1. Start PHP + MySQL (e.g. `docker compose up -d web db`).
2. From this folder:
   ```bash
   npm install
   npm run dev
   ```
3. Open **http://localhost:5173**. Vite proxies **`/api/*`** to **`VITE_API_PROXY_TARGET`** (default `http://127.0.0.1:8081`).

## Development (API + frontend in Docker)

```bash
docker compose up -d
```

The `frontend` service runs Vite with **`VITE_API_PROXY_TARGET=http://web`**, so the browser calls **`http://localhost:5173/api/...`** and the dev server forwards to the `web` container.

## Production build

```bash
npm run build
```

Output: `frontend/dist/`. Serve those static files with Nginx (or copy under your PHP host) and set **`VITE_API_BASE_URL`** at build time to your public API origin if the SPA is not on the same host as the API.

## Environment

| Variable | Where | Purpose |
|----------|--------|--------|
| `VITE_API_PROXY_TARGET` | root `.env` or shell | Vite dev proxy target (PHP backend). |
| `VITE_API_BASE_URL` | build-time `.env.production` | Full API origin for production fetch (empty = same-origin relative `/api`). |
