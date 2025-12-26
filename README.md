# JWT Microservices Demo (PHP, Docker, No Database)

This project is a practical demonstration of stateless authentication in a microservices architecture using JWT (JSON Web Tokens), refresh tokens, and HTTPS (SSL/TLS).
It is implemented with PHP and Docker and intentionally avoids using a database to keep the concepts clear and focused.

The main goal of this demo is to show how microservices can authenticate requests offline, even when the Auth service is unavailable, and how to reduce unnecessary calls to the Auth service by trusting short-lived access tokens for a limited period of time.

---

## Why this demo exists

In many real-world systems, calling the Auth service on every API request:
- increases latency,
- creates a single point of failure,
- and limits scalability.

This demo shows an alternative approach:

- The Auth service issues short-lived JWT access tokens.
- Other services verify tokens locally (offline) using a shared public key.
- As long as the access token is valid, no Auth service call is required.
- When the access token expires, a refresh token is used to obtain a new one.
- Only when the refresh token expires does the user need to log in again.

This approach keeps services stateless, decoupled, and resilient.

---

## Architecture overview

The system consists of three backend services and a simple frontend.

### Auth Service
- Validates user credentials (hardcoded for demo purposes).
- Issues:
  - Access token: RS256-signed JWT with a very short TTL (10 seconds).
  - Refresh token: opaque random token with a longer TTL (10 minutes).
- Rotates refresh tokens on every refresh request.
- Uses no database (refresh tokens are stored in a simple file for demo purposes).

### Catalog Service
- Represents a protected API (for example, a product catalog).
- Verifies JWT access tokens offline using the public key.
- Does not call the Auth service during normal requests.

### Orders Service
- Represents another protected API (for example, an order system).
- Uses the same offline JWT validation approach as the Catalog service.
- Demonstrates how multiple microservices trust the same access token.

### Frontend (Demo UI)
- Simulates a client application (browser / mobile-style behavior).
- Allows:
  - Login via the Auth service
  - Calling the Catalog and Orders APIs
  - Refreshing the access token using the refresh token
- Demonstrates what happens when:
  - the access token expires,
  - the refresh token is still valid,
  - or the Auth service is temporarily unavailable.

---

## Key concepts demonstrated

- Stateless authentication using JWT
- Offline token verification in microservices
- Reduced dependency on the Auth service
- Refresh token flow to renew access tokens
- SSL/TLS using self-signed certificates
- No database required for the demo

---

## JWT Microservices Demo PHP with NO Database

This demo shows:
- Auth service issues RS256 JWT access tokens (10s TTL) after a hardcoded login.
- Auth service issues opaque refresh tokens (10 min TTL) and rotates them on every refresh.
- Catalog and Orders verify JWT offline using the public key (shared/jwt-public.pem) with no calls to the Auth service for normal API requests.
- Refresh token storage is file-based (no DB) so it survives across requests inside the Auth container.

---

## Repository layout

- auth/ — Auth service (issues tokens, refresh endpoint, file-based refresh store)
- catalog/ — Catalog API (verifies JWT and returns products)
- orders/ — Orders API (verifies JWT and returns a ping/pong response)
- caddy/ — Caddy configuration acting as TLS terminator and reverse proxy for /api/*
- frontend/ — Single-page demo UI (talks to /api/auth, /api/catalog, /api/orders)
- shared/ — Shared assets (contains jwt-private.pem and jwt-public.pem)
- certs/ — Local TLS certificates used by Caddy for https://localhost

---

## Quick start (Docker)

1. Build and start the demo using Docker Compose:

    docker compose up --build

2. Open the demo in your browser:

    https://localhost/

3. Login using the demo credentials:

    Email: demo@example.com
    Password: secret123

4. After login, the frontend stores an access token (in memory) and a refresh token.
The access token expires quickly (10 seconds). When the frontend receives a 401 response, it calls POST /api/auth/refresh to rotate the refresh token and obtain a new access token, then retries the original API call.

---

## Useful endpoints

POST /api/auth/login
Body: { "email": "...", "password": "..." }
Returns: access_token and refresh_token

POST /api/auth/refresh
Body: { "refresh_token": "..." }
Rotates the refresh token and returns a new token pair

GET /api/catalog/products
Requires: Authorization: Bearer <access_token>
Returns demo products

GET /api/orders/ping
Requires: Authorization: Bearer <access_token>
Returns pong and user claims

---

## Keys and certificates

Shared JWT keys:
- shared/jwt-private.pem (private key, used only by Auth service)
- shared/jwt-public.pem (public key, used by Catalog and Orders)

### Regenerate JWT keys

    openssl genpkey -algorithm RSA -out shared/jwt-private.pem -pkeyopt rsa_keygen_bits:2048
    openssl rsa -pubout -in shared/jwt-private.pem -out shared/jwt-public.pem

TLS certificates for Caddy:
- certs/localhost.pem
- certs/localhost-key.pem

### Regenerate TLS certificate

    openssl req -x509 -nodes -newkey rsa:2048 -keyout certs/localhost-key.pem -out certs/localhost.pem -days 365 -config certs/openssl.cnf

---

## Development notes

- Each service runs PHP 8.3 using the built-in PHP web server.
- Dependencies are installed via Composer (firebase/php-jwt).
- Refresh tokens are persisted to /tmp/refresh_store.json inside the Auth container.
- This design is intentionally simple and not production-ready.

---

## Troubleshooting

- invalid_credentials: ensure correct login credentials are sent as JSON.
- Missing Bearer token: ensure the Authorization header is present.
- Token expired: refresh the token or log in again.
- Windows users: prefer curl.exe or Invoke-RestMethod instead of PowerShell’s curl alias.

---

## Security disclaimer

This project is for educational purposes only.
Do not use this implementation directly in production. Real systems should use secure key management, durable refresh token storage, and proper monitoring.

---

## License

This demo is provided as-is for learning and experimentation.
