#!/bin/sh

cd /var/www

# Instalar dependências PHP se vendor não existir
if [ -f composer.json ] && [ ! -d vendor ]; then
    composer install --no-interaction --optimize-autoloader
fi

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

php artisan key:generate --force
php artisan migrate --force

# Iniciar PHP-FPM
exec php-fpm
