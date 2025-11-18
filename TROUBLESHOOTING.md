# 🔧 Troubleshooting - Facturación SUNAT API

Guía de solución de problemas comunes.

## 🚨 Error 500 - Internal Server Error

### Síntomas
```
127.0.0.1 - "GET /index.php" 500
```

### Causas Comunes

#### 1. APP_KEY No Configurado ⭐ (Más común)

**Solución:**

En la terminal de EasyPanel:
```bash
# Generar APP_KEY
php artisan key:generate --show
```

Copiar el resultado y agregarlo a las **Variables de Entorno** en EasyPanel:
```
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Luego **reiniciar el contenedor**.

#### 2. Permisos de Archivos

**Solución:**
```bash
# En la terminal del contenedor
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

#### 3. Caché Corrupto

**Solución:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Luego regenerar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔍 Herramientas de Debugging

### 1. Script de Debugging Automático

Ejecutar en la terminal del contenedor:
```bash
bash /var/www/html/docker/debug.sh
```

Este script verifica:
- APP_KEY
- Conexión a base de datos
- Permisos de archivos
- Extensiones PHP
- Logs de Laravel
- Archivos críticos

### 2. Página de Información (Solo Desarrollo)

Acceder desde el navegador:
```
https://tu-dominio.com/info.php
```

Muestra:
- Estado de PHP
- Variables de entorno
- Extensiones instaladas
- Permisos de directorios
- Últimos logs de Laravel

**⚠️ IMPORTANTE:** Eliminar `public/info.php` en producción.

### 3. Ver Logs de Laravel

```bash
# Ver últimos 50 logs
tail -50 /var/www/html/storage/logs/laravel.log

# Ver logs en tiempo real
tail -f /var/www/html/storage/logs/laravel.log

# Ver todos los logs
cat /var/www/html/storage/logs/laravel.log
```

### 4. Ver Logs de Nginx

```bash
# Access logs
tail -50 /var/log/nginx/access.log

# Error logs
tail -50 /var/log/nginx/error.log
```

### 5. Ver Logs de PHP-FPM

```bash
# Logs de PHP-FPM
tail -50 /var/log/supervisor/supervisord.log
```

---

## 🗄️ Problemas con Base de Datos

### Error: "Could not connect to database"

**Verificar conexión:**
```bash
# Test de conexión manual
php -r "
try {
    \$pdo = new PDO('mysql:host=facturacion-sunat-db;port=3306;dbname=facturacion_sunat', 'sunat_user', 'tu_password');
    echo 'Conexión exitosa\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"
```

**Soluciones:**

1. **Verificar que MySQL esté corriendo:**
   - En EasyPanel, ve a Services
   - Verifica que `facturacion-sunat-db` esté en estado "Running"

2. **Verificar credenciales:**
   ```bash
   # Ver variables de entorno
   env | grep DB_
   ```

3. **Verificar conectividad de red:**
   ```bash
   # Ping al servicio MySQL
   ping -c 3 facturacion-sunat-db
   ```

4. **Revisar logs de MySQL:**
   - En EasyPanel, ve a `facturacion-sunat-db`
   - Revisa los logs del servicio

---

## 🔴 Redis No Conecta

### Error: "Connection refused" en Redis

**Verificar:**
```bash
# Test de conexión a Redis
redis-cli -h facturacion-sunat-redis ping
```

**Soluciones:**

1. **Verificar que Redis esté corriendo:**
   - En EasyPanel, Services
   - Verifica `facturacion-sunat-redis`

2. **Cambiar a driver de base de datos temporalmente:**

   Variables de entorno:
   ```
   CACHE_DRIVER=database
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   ```

---

## 🔐 Problemas con Supervisor

### Error: "Can't drop privilege as nonroot user"

**Causa:** Dockerfile ejecuta supervisord como usuario no-root.

**Solución:** El Dockerfile debe ejecutar supervisord como root. Verificar que **NO** tenga:
```dockerfile
USER www  # ❌ Esta línea NO debe estar antes de CMD
```

### Error: "supervisor log directory does not exist"

**Solución:**
```bash
# Crear directorio de logs
mkdir -p /var/log/supervisor
```

---

## 📦 Extensión PHP Faltante

### Error: "The intl PHP extension is required"

**Verificar:**
```bash
php -m | grep intl
```

Si no aparece, verificar que el Dockerfile tenga:
```dockerfile
RUN apk add --no-cache icu-dev icu-libs
RUN docker-php-ext-install intl
```

**Solución:**
- Rebuild del contenedor con el Dockerfile actualizado

---

## 🌐 Nginx Devuelve 404

### Error: "404 Not Found" en todas las rutas

**Causa:** Configuración de Nginx incorrecta.

**Verificar:**
```bash
# Ver configuración de Nginx
cat /etc/nginx/nginx.conf

# Test de configuración
nginx -t
```

**Solución:**
Asegurarse de que `nginx.conf` tenga:
```nginx
root /var/www/html/public;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 🔄 Migraciones No Se Ejecutan

### Las tablas no se crean

**Ejecutar manualmente:**
```bash
# Ver estado de migraciones
php artisan migrate:status

# Ejecutar migraciones
php artisan migrate --force

# Rollback y re-migrar
php artisan migrate:fresh --force
```

**Ver tablas en base de datos:**
```bash
# Conectar a MySQL
mysql -h facturacion-sunat-db -u sunat_user -p facturacion_sunat

# Luego en MySQL:
SHOW TABLES;
```

---

## 🔑 Certificado SUNAT

### Error al enviar comprobantes a SUNAT

**Verificar certificado:**
```bash
# Ver si existe el certificado
ls -la /var/www/html/storage/certificates/

# Verificar permisos
ls -la /var/www/html/storage/certificates/*.pfx
```

**Variables necesarias:**
```env
SUNAT_CERTIFICATE_PATH=/var/www/html/storage/certificates/certificado.pfx
SUNAT_CERTIFICATE_PASSWORD=tu_password
```

---

## 🚀 Performance Issues

### La aplicación está lenta

**Optimizaciones:**

1. **Caché de configuración:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Optimizar autoloader:**
   ```bash
   composer dump-autoload --optimize
   ```

3. **Verificar queue workers:**
   ```bash
   # Ver si los workers están corriendo
   supervisorctl status
   ```

---

## 🧪 Comandos Útiles de Testing

### Verificar Estado General

```bash
# Ver estado de todos los servicios
supervisorctl status

# Test de conectividad
php artisan db:show

# Verificar rutas
php artisan route:list

# Limpiar todo y regenerar
php artisan optimize:clear
php artisan optimize
```

### Verificar Extensiones PHP

```bash
# Ver todas las extensiones
php -m

# Versión de PHP
php -v

# Información completa
php -i
```

---

## 📞 Obtener Ayuda

Si ninguna de estas soluciones funciona:

1. **Ejecuta el script de debugging:**
   ```bash
   bash /var/www/html/docker/debug.sh
   ```

2. **Captura los logs completos:**
   ```bash
   # Laravel logs
   cat /var/www/html/storage/logs/laravel.log > debug.txt

   # Nginx logs
   cat /var/log/nginx/error.log >> debug.txt

   # Supervisor logs
   cat /var/log/supervisor/supervisord.log >> debug.txt
   ```

3. **Accede a la página de info:**
   ```
   https://tu-dominio.com/info.php
   ```

4. **Contacta soporte:**
   - GitHub Issues
   - WhatsApp: https://wa.link/z50dwk
   - Documentación: https://apigo.apuuraydev.com/

---

## ✅ Checklist de Verificación

Antes de pedir ayuda, verifica:

- [ ] APP_KEY está configurado
- [ ] MySQL está corriendo y accesible
- [ ] Redis está corriendo (o deshabilitado)
- [ ] Permisos de storage/ y bootstrap/cache/
- [ ] Extensión intl instalada
- [ ] Logs de Laravel revisados
- [ ] Variables de entorno correctas
- [ ] Certificado SUNAT subido (si aplica)
- [ ] Migraciones ejecutadas

---

**Última actualización:** 2025-11-18
