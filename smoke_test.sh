#!/usr/bin/env bash
set -euo pipefail

echo "Login to get JWT..."
TOKEN=$(curl -sk https://localhost/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com","password":"secret123"}' | python -c 'import sys,json; print(json.load(sys.stdin)["access_token"])')

echo "Calling catalog..."
curl -sk https://localhost/api/catalog/products -H "Authorization: Bearer $TOKEN" | python -m json.tool

echo "Calling orders..."
curl -sk https://localhost/api/orders/ping -H "Authorization: Bearer $TOKEN" | python -m json.tool
