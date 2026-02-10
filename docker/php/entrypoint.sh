#!/bin/sh

cd /var/www

# Instalar dependências npm se node_modules não existir
if [ -f package.json ] && [ ! -d node_modules ]; then
    npm install
fi

# Gerar caches do Laravel (apenas se artisan existir)
if [ -f artisan ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Iniciar PHP-FPM
exec php-fpm
