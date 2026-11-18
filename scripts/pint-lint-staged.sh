#!/bin/bash
set -e
[ $# -eq 0 ] && exit 0

if ! docker compose exec -T app echo "" > /dev/null 2>&1; then
    echo "⚠️  Docker não está rodando. Inicie com 'docker compose up -d' antes de commitar."
    echo "   PHP não foi verificado localmente — o CI do GitHub Actions irá verificar."
    exit 0
fi

HOST_ROOT=$(git rev-parse --show-toplevel)
CONTAINER_ROOT="/var/www"
ARGS=()
for file in "$@"; do
    ARGS+=("${file/$HOST_ROOT/$CONTAINER_ROOT}")
done
docker compose exec -T app ./vendor/bin/pint "${ARGS[@]}"
