#!/usr/bin/env bash
set -euo pipefail

echo "==> PHP lint (Pint)..."
docker compose exec app ./vendor/bin/pint --test

echo "==> JS lint (ESLint)..."
docker compose exec vite npm run lint

echo "==> All checks passed."
