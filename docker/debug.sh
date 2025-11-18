#!/bin/bash
# Script de debugging para la aplicación

echo "=========================================="
echo "🔍 DEBUGGING - Facturación SUNAT API"
echo "=========================================="
echo ""

# Verificar APP_KEY
echo "1️⃣ Verificando APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "   ❌ APP_KEY NO ESTÁ CONFIGURADO"
    echo "   ⚠️  Genera uno con: php artisan key:generate --show"
else
    echo "   ✅ APP_KEY está configurado"
fi
echo ""

# Verificar conexión a base de datos
echo "2️⃣ Verificando conexión a base de datos..."
php artisan db:show 2>/dev/null && echo "   ✅ Base de datos OK" || echo "   ❌ Error en base de datos"
echo ""

# Verificar permisos
echo "3️⃣ Verificando permisos..."
ls -la storage/ | head -5
echo ""

# Verificar extensiones PHP
echo "4️⃣ Extensiones PHP instaladas:"
php -m | grep -E "(intl|pdo|mysql|xml|mbstring|zip)"
echo ""

# Ver últimos logs de Laravel
echo "5️⃣ Últimos 20 logs de Laravel:"
echo "=========================================="
if [ -f storage/logs/laravel.log ]; then
    tail -20 storage/logs/laravel.log
else
    echo "   ⚠️  No hay logs todavía"
fi
echo ""

# Verificar archivos críticos
echo "6️⃣ Verificando archivos críticos..."
[ -f .env ] && echo "   ✅ .env existe" || echo "   ❌ .env NO existe"
[ -f bootstrap/cache/config.php ] && echo "   ✅ Config cache existe" || echo "   ⚠️  Config cache NO existe"
[ -f bootstrap/cache/routes-v7.php ] && echo "   ✅ Routes cache existe" || echo "   ⚠️  Routes cache NO existe"
echo ""

# Test simple de PHP
echo "7️⃣ Test de PHP..."
php -r "echo '   ✅ PHP funciona correctamente: ' . phpversion() . PHP_EOL;"
echo ""

echo "=========================================="
echo "✅ Debugging completado"
echo "=========================================="
