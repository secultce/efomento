#!/bin/sh
set -eu

cd /var/www

# Match the bind mount owner on Linux without requiring host UID/GID configuration.
if [ "$(id -u)" = "0" ] && [ "$(stat -c %u /var/www)" != "0" ]; then
    groupmod -o -g "$(stat -c %g /var/www)" devuser
    usermod -o -u "$(stat -c %u /var/www)" -g devuser devuser
    chown -R devuser:devuser /home/devuser
    chown devuser:devuser /proc/self/fd/1 /proc/self/fd/2
    exec gosu devuser "$0" "$@"
fi

# Only the init service initializes the shared checkout; Compose waits for it to exit.
if [ "${1:-}" != "initialize" ]; then
    exec "$@"
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Instalar dependências PHP se vendor não existir
if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader
fi

# Instalar as versões do lockfile antes de iniciar o Vite.
if [ -f package.json ]; then
    npm ci
fi

# Configurar git hooks via Husky
if [ -f node_modules/.bin/husky ] && [ -d .git ]; then
    node node_modules/.bin/husky 2>/dev/null || true
fi

# Formatar código PHP com Pint
if [ -f vendor/bin/pint ]; then
    ./vendor/bin/pint
fi

# Descartar configuração antiga antes de verificar/gerar a chave.
php artisan config:clear

# Gerar APP_KEY só quando não há chave efetiva (env do processo ou .env parseado),
# evitando invalidar sessões/dados criptografados a cada boot
APP_KEY_EFFECTIVE=$(php -r '
    $key = getenv("APP_KEY");
    if (($key === false || $key === "") && is_file(".env")) {
        foreach (file(".env", FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match("/^APP_KEY=(.*)$/", $line, $m)) {
                $key = trim(trim($m[1]), "\"\x27");
                break;
            }
        }
    }
    echo is_string($key) ? $key : "";
')
if [ -z "$APP_KEY_EFFECTIVE" ]; then
    php artisan key:generate --force
fi

# Gerar caches do Laravel (apenas se artisan existir)
if [ -f artisan ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

php artisan migrate --force
php artisan storage:link --force

# Sinalizar ao Compose que a inicialização terminou com sucesso.
exit 0
