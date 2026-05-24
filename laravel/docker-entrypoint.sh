#!/bin/bash
# Entrypoint del contenedor web (Apache + PHP).
# Instala dependencias si vendor/ no existe, espera a MariaDB, lanza
# migraciones y seeders, y arranca Apache.
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    echo "[entrypoint] Instalando dependencias de Composer..."
    composer install --no-interaction --no-progress --optimize-autoloader
fi

# Esperamos a MariaDB.
echo "[entrypoint] Esperando a MariaDB..."
INTENTOS=0
until mariadb -h"${DB_HOST}" -u"${DB_MIGRATE_USER}" -p"${DB_MIGRATE_PASSWORD}" -e "SELECT 1;" >/dev/null 2>&1; do
    INTENTOS=$((INTENTOS+1))
    if [ "$INTENTOS" -gt 30 ]; then
        echo "[entrypoint] MariaDB no responde. Abortando."
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] MariaDB lista."

# Permisos por si el bind-mount los pisa.
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Generar APP_KEY si no existe.
# Aseguramos primero que la línea APP_KEY= exista (key:generate la sustituye
# in-place y aborta si no la encuentra).
if [ ! -f .env ]; then
    echo "[entrypoint] ERROR: .env no encontrado en /var/www/html. ¿Copiaste .env.example a laravel/.env?"
    exit 1
fi
if ! grep -q "^APP_KEY=" .env; then
    echo "APP_KEY=" >> .env
fi
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] Generando APP_KEY..."
    php artisan key:generate --force
fi

# Migraciones y seeders (con usuario root, que tiene DDL).
echo "[entrypoint] Ejecutando migraciones..."
DB_USERNAME="${DB_MIGRATE_USER}" DB_PASSWORD="${DB_MIGRATE_PASSWORD}" \
    php artisan migrate --force

echo "[entrypoint] Ejecutando seeders..."
DB_USERNAME="${DB_MIGRATE_USER}" DB_PASSWORD="${DB_MIGRATE_PASSWORD}" \
    php artisan db:seed --force || echo "[entrypoint] Seeders ya aplicados o fallaron (no bloqueante)."

if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "[entrypoint] Arrancando Apache."
exec "$@"
