# JWT Microservices Demo (Mobile Refresh Token) — Fixed

This demo shows:
- Auth service issues **RS256 JWT access tokens** (10s TTL) after a hardcoded login.
- Auth service issues **opaque refresh tokens** (10 min TTL) and **rotates** them on every refresh.
- Catalog + Orders verify JWT **offline** using the public key (`shared/jwt-public.pem`) — **no calls to auth service** for normal API requests.
- Refresh token storage is **file-based** (no DB) so it survives across requests inside the auth container.

## Run
```bash
docker compose up --build
```

Open:
- https://localhost/

Login credentials:
- demo@example.com / secret123

## Test expiry quickly
- Access token expires in 10 seconds.
- Click "Call Catalog", wait 10–15s, click again → UI will refresh token and retry automatically.
