This is a minimal demo showing **RS256 JWT** issued by an auth service and verified by two microservices
*without calling the auth service* (they verify offline using the public key).

Run:
  docker compose up --build

Open:
  https://localhost/

Accept the self-signed certificate in your browser (it's in ./certs).
