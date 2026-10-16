#!/bin/sh
set -e

cd /var/www/

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Rodando migrations..."
  php artisan migrate --force --isolated

  echo "Criando link simbólico do storage..."
  php artisan storage:link --force

  echo "Gerando caches..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"