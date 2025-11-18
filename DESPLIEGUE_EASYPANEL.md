# 🚀 Guía de Despliegue en EasyPanel

Esta guía te ayudará a desplegar el API de Facturación SUNAT en tu servidor usando EasyPanel.

## 📋 Prerequisitos

- Servidor con EasyPanel instalado
- Cuenta de GitHub (para conectar el repositorio)
- Certificado digital SUNAT (.pfx)
- Dominio configurado (opcional, recomendado)

## 🔧 Paso 1: Preparar el Repositorio

1. **Fork este repositorio** a tu cuenta de GitHub
2. **Clona tu fork** en tu máquina local:
   ```bash
   git clone https://github.com/TU_USUARIO/facturacion-sunat-api.git
   cd facturacion-sunat-api
   ```

## 🐳 Paso 2: Crear Proyecto en EasyPanel

1. **Accede a tu panel de EasyPanel** (https://tu-servidor:3000)
2. **Crea un nuevo proyecto**:
   - Haz clic en "Create Project"
   - Nombre: `facturacion-sunat-api`
   - Selecciona "Git Repository"

3. **Conecta tu repositorio**:
   - URL del repositorio: `https://github.com/TU_USUARIO/facturacion-sunat-api`
   - Branch: `main` o la rama que estés usando
   - Dockerfile path: `Dockerfile`

## 🗄️ Paso 3: Configurar Base de Datos

### Opción A: Usar Servicio de EasyPanel (Recomendado)

1. En EasyPanel, ve a "Services"
2. Crea un nuevo servicio MySQL:
   - Nombre: `facturacion-sunat-db`
   - Imagen: `mysql:8.0`
   - Variables de entorno:
     ```
     MYSQL_ROOT_PASSWORD=tu_password_seguro
     MYSQL_DATABASE=facturacion_sunat
     MYSQL_USER=sunat_user
     MYSQL_PASSWORD=tu_password_db
     ```

### Opción B: Usar Base de Datos Externa

Si ya tienes una base de datos MySQL/PostgreSQL, solo necesitas los datos de conexión.

## 📦 Paso 4: Configurar Redis (Opcional pero Recomendado)

1. En EasyPanel, crea un servicio Redis:
   - Nombre: `facturacion-sunat-redis`
   - Imagen: `redis:7-alpine`

## ⚙️ Paso 5: Variables de Entorno

En la configuración del proyecto en EasyPanel, agrega estas variables de entorno:

### Variables Básicas
```env
APP_NAME="Facturación SUNAT API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_KEY=
```

### Base de Datos
```env
DB_CONNECTION=mysql
DB_HOST=facturacion-sunat-db
DB_PORT=3306
DB_DATABASE=facturacion_sunat
DB_USERNAME=sunat_user
DB_PASSWORD=tu_password_db
```

### Redis (si lo configuraste)
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=facturacion-sunat-redis
REDIS_PORT=6379
```

### Configuración SUNAT
```env
# Ambiente SUNAT (beta para pruebas, production para producción)
SUNAT_ENVIRONMENT=beta

# Credenciales SUNAT
SUNAT_SOL_USER=
SUNAT_SOL_PASS=

# URLs SUNAT
SUNAT_API_URL=https://api-cpe.sunat.gob.pe/v1
SUNAT_BETA_URL=https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
```

### Otras Configuraciones
```env
LOG_CHANNEL=stack
LOG_LEVEL=info

BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
```

## 🔑 Paso 6: Generar APP_KEY

1. Una vez desplegado el contenedor, ejecuta en la terminal de EasyPanel:
   ```bash
   php artisan key:generate --show
   ```

2. Copia la clave generada y actualiza la variable `APP_KEY` en EasyPanel.

## 📜 Paso 7: Configurar Certificados SUNAT

Necesitas subir tu certificado digital SUNAT (.pfx) al servidor:

1. **Usando volúmenes de EasyPanel**:
   - Ve a "Volumes" en tu proyecto
   - Crea un volumen: `certificates-data`
   - Monta en: `/var/www/html/storage/certificates`

2. **Subir certificado**:
   - Usa el explorador de archivos de EasyPanel
   - O usa SCP/SFTP para subir el archivo .pfx al volumen

3. **Variables de entorno para certificado**:
   ```env
   SUNAT_CERTIFICATE_PATH=/var/www/html/storage/certificates/certificado.pfx
   SUNAT_CERTIFICATE_PASSWORD=tu_password_certificado
   ```

## 🌐 Paso 8: Configurar Dominio y SSL

1. En EasyPanel, ve a "Domains"
2. Agrega tu dominio: `api.tudominio.com`
3. Habilita SSL automático (Let's Encrypt)

## 🚀 Paso 9: Desplegar

1. **Haz clic en "Deploy"** en EasyPanel
2. Espera a que se construya la imagen (primera vez puede tardar 5-10 minutos)
3. Una vez completado, el servicio estará disponible

## ✅ Paso 10: Verificar Instalación

1. **Ejecutar migraciones** (si no se ejecutaron automáticamente):
   ```bash
   php artisan migrate --force
   ```

2. **Verificar la API**:
   - Visita: `https://tu-dominio.com/api/health`
   - Deberías ver: `{"status": "ok"}`

3. **Crear usuario administrador** (si es necesario):
   ```bash
   php artisan tinker
   ```
   Luego en el shell de Tinker:
   ```php
   $user = new App\Models\User();
   $user->name = 'Admin';
   $user->email = 'admin@tuempresa.com';
   $user->password = Hash::make('tu_password_seguro');
   $user->save();
   ```

## 🔄 Paso 11: Configurar Workers de Cola (Opcional)

Si necesitas procesamiento en segundo plano:

1. El Dockerfile ya incluye supervisord con workers de Laravel Queue
2. Los workers se inician automáticamente con el contenedor
3. Puedes verificar su estado en los logs de EasyPanel

## 📊 Monitoreo y Logs

### Ver Logs en Tiempo Real
En EasyPanel, ve a la pestaña "Logs" de tu aplicación.

### Logs Específicos de Laravel
```bash
# Logs de aplicación
tail -f storage/logs/laravel.log

# Logs de workers
tail -f storage/logs/worker.log
```

## 🔒 Seguridad Importante

1. **Cambia todas las contraseñas por defecto**
2. **Configura firewall** en tu servidor
3. **Mantén actualizado** PHP y las dependencias
4. **Habilita HTTPS** siempre en producción
5. **Respalda regularmente** la base de datos y certificados
6. **No commits el archivo .env** al repositorio
7. **Protege tu certificado SUNAT** (.pfx)

## 🔧 Comandos Útiles

### Limpiar Cachés
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimizar para Producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Ejecutar Migraciones
```bash
php artisan migrate --force
```

### Ver Estado de la Base de Datos
```bash
php artisan db:show
```

## 🐛 Resolución de Problemas

### Error: "Could not connect to database"
- Verifica que el servicio MySQL esté corriendo
- Revisa las credenciales en las variables de entorno
- Asegúrate de que el host DB_HOST sea correcto

### Error: "Permission denied"
- Ejecuta: `chmod -R 775 storage bootstrap/cache`
- Verifica que el usuario sea `www:www`

### Error: "No application encryption key"
- Genera la clave: `php artisan key:generate`
- Actualiza APP_KEY en las variables de entorno

### Certificado SUNAT no funciona
- Verifica la ruta del certificado
- Confirma que el password sea correcto
- Asegúrate de que el archivo .pfx esté en el volumen correcto

## 📱 Contacto y Soporte

Si tienes problemas con el despliegue:
- Revisa la documentación oficial: https://apigo.apuuraydev.com/
- Video tutoriales: [YouTube Playlist](https://www.youtube.com/watch?v=HrrEdjY_7MU&list=PLfwfiNJ5Qw-ZlCfGnWjnILOI4OJfJkGp5)
- WhatsApp: https://wa.link/z50dwk

## 🎉 ¡Listo!

Tu API de Facturación SUNAT está ahora desplegada en EasyPanel y lista para usar.

### Próximos Pasos:
1. Configurar tus empresas y sucursales
2. Subir productos/servicios
3. Registrar clientes
4. Comenzar a facturar

---

**Nota**: Esta es una implementación base. Asegúrate de personalizar según tus necesidades específicas y cumplir con todas las normativas de SUNAT vigentes.
