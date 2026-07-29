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

# Configurar git hooks via Husky
if [ -f node_modules/.bin/husky ] && [ -d .git ]; then
    node node_modules/.bin/husky 2>/dev/null || true
fi

# Formatar código PHP com Pint
if [ -f vendor/bin/pint ]; then
    ./vendor/bin/pint
fi

# Gerar caches do Laravel (apenas se artisan existir)
if [ -f artisan ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

php artisan key:generate --force
php artisan migrate --force
php artisan storage:link

# Iniciar PHP-FPM
exec php-fpm
