#!/bin/bash
set -e

echo "🚀 Iniciando aplicación de Facturación SUNAT..."

# Esperar a que la base de datos esté lista
echo "⏳ Esperando conexión a base de datos..."
max_tries=30
count=0

# Script PHP simple para verificar conexión a DB sin usar intl
check_db() {
    php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
    " 2>/dev/null
}

until check_db || [ $count -eq $max_tries ]; do
    count=$((count+1))
    echo "Intento $count de $max_tries..."
    sleep 2
done

if [ $count -eq $max_tries ]; then
    echo "⚠️  Advertencia: No se pudo conectar a la base de datos"
else
    echo "✅ Conexión a base de datos exitosa"
fi

# Crear enlace simbólico de storage si no existe
if [ ! -L public/storage ]; then
    echo "🔗 Creando enlace simbólico de storage..."
    php artisan storage:link || true
fi

# Ejecutar migraciones en producción
if [ "${APP_ENV}" = "production" ]; then
    echo "📊 Ejecutando migraciones..."
    php artisan migrate --force --no-interaction || echo "⚠️  Error en migraciones"
fi

# Limpiar y optimizar cachés
echo "🧹 Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear directorios necesarios si no existen
mkdir -p storage/certificates
mkdir -p storage/app/public/xml
mkdir -p storage/app/public/pdf
mkdir -p storage/logs

echo "✅ Aplicación lista!"
echo "🌐 Servidor escuchando en puerto 80"

# Ejecutar comando principal
exec "$@"
