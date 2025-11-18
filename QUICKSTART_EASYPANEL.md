# ⚡ Quickstart - EasyPanel

Guía rápida para desplegar en 10 minutos.

## 1️⃣ Crear Servicios en EasyPanel

### MySQL
```yaml
Nombre: facturacion-sunat-db
Imagen: mysql:8.0
Variables:
  MYSQL_ROOT_PASSWORD: root_password_change_me
  MYSQL_DATABASE: facturacion_sunat
  MYSQL_USER: sunat_user
  MYSQL_PASSWORD: tu_password_db
```

### Redis
```yaml
Nombre: facturacion-sunat-redis
Imagen: redis:7-alpine
```

## 2️⃣ Crear Aplicación

1. **New App** → **From Git Repository**
2. **Repository**: Tu fork de este repo
3. **Build Type**: Dockerfile
4. **Dockerfile Path**: `Dockerfile`

## 3️⃣ Variables de Entorno (Mínimas)

Copia estas variables al panel de EasyPanel:

```env
APP_NAME=Facturación SUNAT API
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=facturacion-sunat-db
DB_PORT=3306
DB_DATABASE=facturacion_sunat
DB_USERNAME=sunat_user
DB_PASSWORD=tu_password_db

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=facturacion-sunat-redis
REDIS_PORT=6379

SUNAT_ENVIRONMENT=beta
SUNAT_CERTIFICATE_PATH=/var/www/html/storage/certificates/certificado.pfx
SUNAT_CERTIFICATE_PASSWORD=
SUNAT_SOL_USER=
SUNAT_SOL_PASS=
```

## 4️⃣ Volúmenes

Crea estos volúmenes en EasyPanel:

- `storage-data` → `/var/www/html/storage`
- `certificates-data` → `/var/www/html/storage/certificates`

## 5️⃣ Dominio

1. **Domains** → **Add Domain**
2. Agrega: `api.tudominio.com`
3. Habilita SSL (Let's Encrypt)

## 6️⃣ Deploy

1. Haz clic en **Deploy**
2. Espera 5-10 minutos
3. Una vez listo, ejecuta en la terminal:

```bash
# Generar APP_KEY
php artisan key:generate --show

# Ejecutar migraciones
php artisan migrate --force
```

4. Copia el APP_KEY generado y actualízalo en las variables de entorno

## 7️⃣ Subir Certificado

1. Accede al volumen `certificates-data`
2. Sube tu archivo `.pfx` de SUNAT
3. Actualiza las variables de entorno con el nombre y password

## ✅ Verificar

Visita: `https://tu-dominio.com/api/health`

Deberías ver: `{"status": "ok"}`

---

## 🚨 Importante

- Cambia TODAS las contraseñas
- Sube tu certificado SUNAT (.pfx)
- Configura SUNAT_SOL_USER y SUNAT_SOL_PASS
- Para producción: cambia `SUNAT_ENVIRONMENT=production`

## 📚 Documentación Completa

Ver: [DESPLIEGUE_EASYPANEL.md](./DESPLIEGUE_EASYPANEL.md)

## 🆘 Problemas?

1. Revisa los logs en EasyPanel
2. Verifica las variables de entorno
3. Asegúrate de que MySQL y Redis estén corriendo
4. Consulta: https://apigo.apuuraydev.com/
