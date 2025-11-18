# 🐳 Docker - Facturación SUNAT API

Guía completa para ejecutar la aplicación con Docker.

## 📦 Contenido

- `Dockerfile` - Imagen optimizada multi-stage para producción
- `docker-compose.yml` - Orquestación completa (App + MySQL + Redis)
- `docker/` - Archivos de configuración (Nginx, PHP-FPM, Supervisor)

## 🚀 Inicio Rápido

### 1. Clonar el Repositorio

```bash
git clone https://github.com/TU_USUARIO/facturacion-sunat-api.git
cd facturacion-sunat-api
```

### 2. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar configuración
nano .env
```

**Variables importantes a configurar:**
```env
APP_KEY=  # Se generará después
DB_PASSWORD=tu_password_seguro
SUNAT_CERTIFICATE_PATH=/var/www/html/storage/certificates/certificado.pfx
SUNAT_CERTIFICATE_PASSWORD=tu_password_certificado
```

### 3. Iniciar Contenedores

```bash
# Construir e iniciar todos los servicios
docker-compose up -d

# Ver logs
docker-compose logs -f
```

### 4. Configuración Inicial

```bash
# Generar APP_KEY
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Crear enlace simbólico de storage
docker-compose exec app php artisan storage:link
```

### 5. Subir Certificado SUNAT

```bash
# Copiar tu certificado al contenedor
docker cp /ruta/a/tu/certificado.pfx facturacion-sunat-api:/var/www/html/storage/certificates/
```

### 6. Acceder a la Aplicación

La aplicación estará disponible en:
- **API**: http://localhost:8080
- **Health Check**: http://localhost:8080/api/health

## 📋 Comandos Útiles

### Gestión de Contenedores

```bash
# Iniciar servicios
docker-compose up -d

# Detener servicios
docker-compose down

# Ver estado
docker-compose ps

# Ver logs
docker-compose logs -f app

# Reiniciar un servicio
docker-compose restart app
```

### Comandos Artisan

```bash
# Ejecutar cualquier comando artisan
docker-compose exec app php artisan [comando]

# Ejemplos:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
```

### Acceso al Contenedor

```bash
# Acceder a la shell del contenedor
docker-compose exec app sh

# Acceder como root
docker-compose exec -u root app sh
```

### Base de Datos

```bash
# Conectar a MySQL
docker-compose exec db mysql -u sunat_user -p facturacion_sunat

# Backup de base de datos
docker-compose exec db mysqldump -u sunat_user -p facturacion_sunat > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u sunat_user -p facturacion_sunat < backup.sql
```

### Redis

```bash
# Conectar a Redis CLI
docker-compose exec redis redis-cli

# Ver todas las claves
docker-compose exec redis redis-cli KEYS '*'

# Limpiar caché
docker-compose exec redis redis-cli FLUSHALL
```

## 🔧 Estructura Docker

### Servicios

1. **app** - Aplicación Laravel con Nginx + PHP-FPM
   - Puerto: 8080
   - Incluye: Nginx, PHP 8.2, Supervisor, Queue Workers

2. **db** - Base de datos MySQL 8.0
   - Puerto: 3306
   - Datos persistentes en volumen `db-data`

3. **redis** - Redis para caché y colas
   - Puerto: 6379
   - Datos persistentes en volumen `redis-data`

### Volúmenes

- `db-data` - Datos de MySQL
- `redis-data` - Datos de Redis
- `storage-data` - Storage de Laravel
- `certificates-data` - Certificados SUNAT

## 🏗️ Build Personalizado

### Construir Imagen Manualmente

```bash
# Build básico
docker build -t facturacion-sunat-api .

# Build con target específico
docker build --target production -t facturacion-sunat-api:prod .

# Build sin caché
docker build --no-cache -t facturacion-sunat-api .
```

### Usar Solo la Imagen (sin docker-compose)

```bash
# Ejecutar contenedor standalone
docker run -d \
  --name facturacion-sunat \
  -p 8080:80 \
  -e APP_KEY=base64:... \
  -e DB_HOST=host.docker.internal \
  -v $(pwd)/storage:/var/www/html/storage \
  facturacion-sunat-api
```

## 🔍 Troubleshooting

### Error: "Could not connect to database"

```bash
# Verificar que MySQL esté corriendo
docker-compose ps db

# Ver logs de MySQL
docker-compose logs db

# Reiniciar MySQL
docker-compose restart db
```

### Error: "Permission denied" en storage

```bash
# Arreglar permisos
docker-compose exec -u root app chown -R www:www storage bootstrap/cache
docker-compose exec -u root app chmod -R 775 storage bootstrap/cache
```

### Error: "No APP_KEY"

```bash
# Generar nueva clave
docker-compose exec app php artisan key:generate

# Verificar .env
docker-compose exec app cat .env | grep APP_KEY
```

### Contenedor se reinicia constantemente

```bash
# Ver logs del contenedor
docker-compose logs -f app

# Verificar health check
docker inspect facturacion-sunat-api | grep -A 20 Health
```

### Limpiar Todo y Empezar de Nuevo

```bash
# CUIDADO: Esto borrará TODOS los datos
docker-compose down -v
docker-compose up -d
```

## 📊 Monitoreo

### Ver Uso de Recursos

```bash
# Stats en tiempo real
docker stats

# Solo de esta aplicación
docker stats facturacion-sunat-api facturacion-sunat-db facturacion-sunat-redis
```

### Health Checks

```bash
# Verificar salud de la aplicación
curl http://localhost:8080/api/health

# Ver detalles de health check
docker inspect facturacion-sunat-api | jq '.[0].State.Health'
```

## 🔒 Seguridad

### Buenas Prácticas

1. **No uses contraseñas por defecto**
   ```bash
   # Genera contraseñas seguras
   openssl rand -base64 32
   ```

2. **No expongas puertos innecesarios**
   - Comenta los puertos de MySQL y Redis en docker-compose.yml si no los necesitas

3. **Mantén actualizado**
   ```bash
   # Actualizar imágenes
   docker-compose pull
   docker-compose up -d
   ```

4. **Backups regulares**
   ```bash
   # Script de backup automático
   docker-compose exec db mysqldump -u sunat_user -p facturacion_sunat | gzip > backup_$(date +%Y%m%d).sql.gz
   ```

## 🚀 Producción

Para producción, usa EasyPanel u otro orquestador. Ver:
- [DESPLIEGUE_EASYPANEL.md](./DESPLIEGUE_EASYPANEL.md)
- [QUICKSTART_EASYPANEL.md](./QUICKSTART_EASYPANEL.md)

### Variables de Producción

```bash
# Usar archivo de producción
cp .env.production.example .env
# Editar valores
nano .env
```

## 📚 Documentación Adicional

- [Documentación Laravel](https://laravel.com/docs)
- [Docker Compose](https://docs.docker.com/compose/)
- [API SUNAT](https://apigo.apuuraydev.com/)

## 🆘 Soporte

- Issues: [GitHub Issues](https://github.com/TU_USUARIO/facturacion-sunat-api/issues)
- WhatsApp: https://wa.link/z50dwk
- Documentación: https://apigo.apuuraydev.com/

---

**Nota**: Este setup de Docker está optimizado para desarrollo local. Para producción, considera usar EasyPanel, Kubernetes, o servicios administrados.
