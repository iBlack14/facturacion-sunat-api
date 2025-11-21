# SUNAT Facturación Electrónica - Plugin WordPress

Plugin de facturación electrónica para WordPress que permite a los usuarios emitir facturas y boletas electrónicas válidas ante SUNAT (Perú).

## 🚀 Características

- ✅ **Multi-usuario**: Cada usuario WordPress puede configurar su propia empresa y certificado
- ✅ **Certificados digitales**: Soporta certificados .pfx, .p12 y .pem
- ✅ **Facturas y Boletas**: Emisión automática según el tipo de documento del cliente (RUC/DNI)
- ✅ **Integración WooCommerce**: Emisión automática al completar pedidos
- ✅ **Gestión de clientes**: CRUD completo de clientes
- ✅ **Panel frontend**: Los usuarios gestionan todo desde el frontend
- ✅ **Panel admin**: Estadísticas y logs del sistema
- ✅ **Descarga de PDF**: Generación automática de PDFs de comprobantes
- ✅ **Modo Beta/Producción**: Pruebas en SUNAT Beta antes de pasar a producción

## 📋 Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- Extensión PHP OpenSSL habilitada
- MySQL 5.6 o superior
- WooCommerce 5.0+ (opcional, solo para integración)

## 🔧 Instalación

### Instalación manual

1. Descarga el plugin
2. Sube la carpeta `sunat-facturacion-electronica` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de WordPress
4. Ve a **SUNAT Facturación → Configuración** y configura la API

### Desde el panel de WordPress

1. Ve a **Plugins → Añadir nuevo**
2. Sube el archivo ZIP del plugin
3. Activa el plugin
4. Configura la API desde **SUNAT Facturación → Configuración**

## ⚙️ Configuración

### 1. Configuración de la API (Admin)

Ve a **SUNAT Facturación → Configuración** y configura:

- **URL de la API**: `https://api-sunat.blxkstudio.com` (o tu propia instancia)
- **Email API**: Tu email registrado en la API
- **Contraseña API**: Tu contraseña de la API
- **Auto-emisión WooCommerce**: Activar para emitir automáticamente al completar pedidos

### 2. Configuración de Usuario (Frontend)

Los usuarios deben configurar:

#### A. Datos de Empresa

1. Accede al shortcode `[sunat_panel]` (crea una página con este shortcode)
2. Ve a **Mi Empresa**
3. Completa los datos:
   - RUC
   - Razón Social
   - Dirección completa
   - Credenciales SOL (opcional)
   - Modo: Beta (pruebas) o Producción
   - Series de facturas y boletas

#### B. Certificado Digital

1. Ve a **Certificado**
2. Sube tu certificado digital (.pfx, .p12 o .pem)
3. Ingresa la contraseña del certificado
4. El certificado se almacena cifrado en el servidor

## 📖 Uso

### Shortcode Principal

Agrega el shortcode en cualquier página:

```
[sunat_panel]
```

Este shortcode muestra el panel completo con:
- Dashboard con estadísticas
- Configuración de empresa
- Gestión de certificado
- CRUD de clientes
- Lista de comprobantes
- Formulario de nueva factura/boleta

### Emitir Factura/Boleta Manual

1. Ve a **Nueva Factura**
2. Selecciona un cliente o ingresa datos manualmente
3. Agrega items (productos/servicios)
4. Clic en **Emitir Comprobante**
5. El sistema detecta automáticamente:
   - RUC (11 dígitos) → Factura
   - DNI (8 dígitos) → Boleta
6. Descarga el PDF del comprobante

### Integración WooCommerce

Si WooCommerce está instalado:

1. El plugin agrega campos de documento en el checkout
2. Al completar un pedido, se emite automáticamente el comprobante
3. El comprobante se asocia al pedido
4. Puedes ver el comprobante en la página del pedido (admin)

## 🗂️ Estructura de Base de Datos

El plugin crea 6 tablas:

- `wp_sunat_companies`: Datos de las empresas (una por usuario)
- `wp_sunat_certificates`: Certificados digitales cifrados
- `wp_sunat_clients`: Clientes de cada usuario
- `wp_sunat_invoices`: Facturas y boletas emitidas
- `wp_sunat_invoice_items`: Items de cada comprobante
- `wp_sunat_logs`: Logs del sistema

## 🔐 Seguridad

- ✅ Certificados almacenados con cifrado AES-256-CBC
- ✅ Directorio de certificados protegido con .htaccess
- ✅ Validación de nonces en todos los formularios
- ✅ Sanitización de todas las entradas
- ✅ Permisos de usuario verificados
- ✅ Preparación de consultas SQL con wpdb

## 🛠️ Desarrollo

### Arquitectura del Plugin

```
sunat-facturacion-electronica/
├── admin/                          # Panel de administración
│   ├── class-admin.php            # Clase principal admin
│   ├── partials/                  # Templates admin
│   ├── css/                       # Estilos admin
│   └── js/                        # Scripts admin
├── includes/                       # Clases principales
│   ├── class-activator.php        # Activación del plugin
│   ├── class-deactivator.php      # Desactivación
│   ├── class-sunat-facturacion.php # Clase principal
│   ├── class-loader.php           # Cargador de hooks
│   ├── class-api-client.php       # Cliente REST API
│   ├── class-database.php         # Abstracción de BD
│   ├── class-certificate-manager.php # Gestión de certificados
│   ├── class-invoice-generator.php   # Generador de facturas
│   └── class-woocommerce-integration.php # Integración WooCommerce
├── public/                         # Panel público (frontend)
│   ├── class-public.php           # Clase principal pública
│   ├── partials/                  # Templates frontend
│   ├── css/                       # Estilos frontend
│   └── js/                        # Scripts frontend
└── sunat-facturacion-electronica.php # Archivo principal
```

### Hooks Disponibles

#### Actions

```php
// Después de emitir una factura
do_action('sunat_after_invoice_emit', $invoice_id, $user_id);

// Después de subir un certificado
do_action('sunat_after_certificate_upload', $certificate_id, $user_id);

// Antes de enviar a SUNAT
do_action('sunat_before_send_to_sunat', $invoice_id);
```

#### Filters

```php
// Modificar datos de factura antes de enviar
$invoice_data = apply_filters('sunat_invoice_data', $invoice_data, $user_id);

// Modificar URL de la API
$api_url = apply_filters('sunat_api_url', $api_url);

// Modificar payload de invoice
$payload = apply_filters('sunat_invoice_payload', $payload, $invoice_data);
```

## 🐛 Troubleshooting

### Error: "No tienes certificado activo"

- Verifica que subiste tu certificado digital
- Verifica que el certificado no haya expirado
- Verifica permisos de escritura en `wp-content/uploads/sunat-certificados/`

### Error: "Error de autenticación con la API"

- Verifica las credenciales en **SUNAT Facturación → Configuración**
- Verifica que tu usuario esté registrado en la API
- Verifica conectividad con la API

### Error: "Certificado inválido o contraseña incorrecta"

- Verifica que el certificado sea .pfx, .p12 o .pem
- Verifica que la contraseña sea correcta
- Verifica que la extensión OpenSSL de PHP esté habilitada

### Comprobantes quedan en "PENDIENTE"

- Verifica que SUNAT esté operativo
- Verifica tus credenciales SOL
- Usa el botón "Reenviar a SUNAT" para reintentar

## 📊 Estadísticas y Logs

### Panel de Estadísticas

Ve a **SUNAT Facturación → Estadísticas** para ver:
- Total de comprobantes emitidos
- Tasa de aceptación por usuario
- Comprobantes por mes
- Gráficas de uso

### Logs del Sistema

Ve a **SUNAT Facturación → Logs** para ver:
- Todos los eventos del sistema
- Filtrar por nivel (info, warning, error)
- Filtrar por usuario
- Fecha y hora de cada evento

## 🔄 Actualizaciones

Para actualizar el plugin:

1. Desactiva el plugin
2. Elimina la carpeta anterior
3. Sube la nueva versión
4. Reactiva el plugin

**Nota**: Las tablas de BD y datos de usuarios se mantienen al desactivar.

## 🤝 Soporte

Para soporte técnico:
- Email: support@blxkstudio.com
- Documentación: [docs.blxkstudio.com](https://docs.blxkstudio.com)

## 📝 Licencia

Este plugin es propiedad de BLXKSTUDIO. Uso restringido según términos de licencia.

## 🎯 Roadmap

- [ ] Notas de crédito y débito
- [ ] Guías de remisión
- [ ] Resumen de boletas
- [ ] Comunicación de baja
- [ ] API REST para integraciones
- [ ] Sistema de licencias
- [ ] Multi-moneda
- [ ] Reportes avanzados

## 👨‍💻 Autor

**BLXKSTUDIO**
- Website: https://blxkstudio.com
- Email: contact@blxkstudio.com

---

**Versión**: 1.0.0
**Última actualización**: 2025
**Compatible con**: WordPress 5.8+, PHP 7.4+
