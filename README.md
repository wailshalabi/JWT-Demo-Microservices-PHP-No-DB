# JWT Microservices Demo PHP with NO Database

This demo shows:
- Auth service issues **RS256 JWT access tokens** (10s TTL) after a hardcoded login.
- Auth service issues **opaque refresh tokens** (10 min TTL) and **rotates** them on every refresh.
- Catalog + Orders verify JWT **offline** using the public key (`shared/jwt-public.pem`) — **no calls to auth service** for normal API requests.
- Refresh token storage is **file-based** (no DB) so it survives across requests inside the auth container.

## Run
# JWT Microservices Demo (Mobile-style Refresh Tokens)

This repository is a small PHP + Docker demonstration showing how to use
short-lived RS256 JWT access tokens together with opaque refresh tokens and
refresh-token rotation. It is intended for learning and experimentation,
not production use.

**Highlights**
- **Access tokens:** RS256 JWTs signed with a private key (`/shared/jwt-private.pem`), very short TTL (10s in the demo).
- **Refresh tokens:** Opaque random tokens stored server-side and rotated on refresh (10 minutes TTL in the demo).
- **Stateless verification:** `catalog` and `orders` services verify JWTs locally using `/shared/jwt-public.pem` (no auth service call required for normal API requests).
- **No DB:** Refresh token storage is file-based inside the `auth` container (`/tmp/refresh_store.json`).

**Repository layout**
- `auth/` — Auth service (issues tokens, refresh endpoint, file-based refresh store).
- `catalog/` — Catalog API (verifies JWT and returns products).
- `orders/` — Orders API (verifies JWT and returns a ping/pong response).
- `caddy/` — Caddy config acting as TLS terminator + reverse proxy for `/api/*`.
- `frontend/` — Single-page demo UI (talks to `/api/auth`, `/api/catalog`, `/api/orders`).
- `shared/` — Shared assets (contains `jwt-private.pem` and `jwt-public.pem`).
- `certs/` — Local TLS certs used by Caddy for `https://localhost`.

## Quick start (Docker)

1. Build and start the demo using Docker Compose (requires Docker):

```powershell
docker compose up --build
```

2. Open the demo in your browser:

```
https://localhost/
```

3. Login using the demo credentials:

- Email: `demo@example.com`
- Password: `secret123`

4. After login the frontend stores an access token (in memory) and a refresh token.
	The access token expires quickly (10s). When the frontend receives a `401`
	it will call `POST /api/auth/refresh` to rotate the refresh token and obtain
	a new access token, then retry the original API call.

## Useful endpoints
- `POST /api/auth/login` — body: `{ "email": "...", "password": "..." }` → returns `access_token` and `refresh_token`.
- `POST /api/auth/refresh` — body: `{ "refresh_token": "..." }` → rotates refresh token and returns a new pair.
- `GET /api/catalog/products` — requires `Authorization: Bearer <access_token>`; returns demo products.
- `GET /api/orders/ping` — requires `Authorization: Bearer <access_token>`; returns pong and user claims.

## Keys and certificates
- Shared JWT keys: `shared/jwt-private.pem` (private) and `shared/jwt-public.pem` (public). These are mounted into service containers by `docker-compose.yml`.
- TLS certs for Caddy: `certs/localhost.pem` and `certs/localhost-key.pem` (already present in this repo).

If you need to regenerate the JWT keys locally, you can use:

```bash
# generate RSA private key
openssl genpkey -algorithm RSA -out shared/jwt-private.pem -pkeyopt rsa_keygen_bits:2048
# extract public key
openssl rsa -pubout -in shared/jwt-private.pem -out shared/jwt-public.pem
```

If you need to regenerate the self-signed TLS cert for `localhost` (used by Caddy):

```bash
openssl req -x509 -nodes -newkey rsa:2048 -keyout certs/localhost-key.pem -out certs/localhost.pem -days 365 -config certs/openssl.cnf
```

## Development notes
- Each service `Dockerfile` uses `composer` to install PHP dependencies (`firebase/php-jwt` is required).
- The demo runs PHP's built-in web server inside each container on port `8080`.
- The `auth` service persists refresh tokens to `/tmp/refresh_store.json` inside the container using file locking — this is intentionally simple for the demo.

## Troubleshooting
- If you get `Missing Bearer token` or `Unauthorized` from the APIs, ensure the frontend is sending `Authorization: Bearer <access_token>` and that the access token is not expired.
- If `jwt-public.pem` or `jwt-private.pem` are missing, recreate them as shown above.
- If you don't have `docker` installed locally, you can still read the code and run PHP files manually, but you'll need PHP 8.3+ and `composer` to install dependencies.

## Security / disclaimer
This project is for demonstration only. Do not use the refresh token storage or key handling in production as-is. Proper production systems should use a secure key management system and a durable, securely-accessed refresh token store.

## License
This demo is provided as-is for learning purposes.
