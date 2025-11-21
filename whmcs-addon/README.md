# Addon WHMCS - Facturación Electrónica SUNAT Perú

Módulo de integración para WHMCS que permite la emisión automática de **Facturas y Boletas electrónicas** para SUNAT (Perú) desde WHMCS.

## Características

✅ **Multi-empresa**: Soporte para múltiples empresas emisoras
✅ **Emisión automática**: Crea comprobantes cuando invoices pasan a Unpaid o Paid
✅ **Detección inteligente**: RUC → Factura, DNI → Boleta
✅ **Mapeo de productos**: Configura códigos SUNAT para tus productos
✅ **Fallback automático**: Productos sin mapeo usan su nombre original
✅ **Estados en tiempo real**: Monitorea estado SUNAT de cada comprobante
✅ **Descarga de archivos**: PDF, XML, CDR directamente desde WHMCS

---

## Requisitos

- WHMCS 7.8 o superior
- PHP 7.4 o superior
- Extensión PHP cURL habilitada
- API de Facturación SUNAT funcionando (ej: https://api-sunat.blxkstudio.com)

---

## Instalación

### 1. Subir archivos

Copia la carpeta `sunat_facturacion` a:

```
/tu-whmcs/modules/addons/sunat_facturacion/
```

La estructura debe quedar:

```
whmcs/
└── modules/
    └── addons/
        └── sunat_facturacion/
            ├── sunat_facturacion.php
            ├── hooks.php
            ├── lib/
            │   ├── SunatApiClient.php
            │   └── InvoiceGenerator.php
            └── templates/
                └── admin/
                    ├── dashboard.php
                    ├── companies.php
                    ├── invoices.php
                    ├── mapping.php
                    ├── logs.php
                    └── style.css
```

### 2. Activar el módulo

1. Ingresa a **Setup → Addon Modules**
2. Busca "**Facturación SUNAT Perú**"
3. Haz clic en **Activate**
4. Configura permisos de acceso (Admin Role Access)

El addon creará automáticamente las tablas en la base de datos:
- `mod_sunat_companies`
- `mod_sunat_invoices`
- `mod_sunat_product_mapping`
- `mod_sunat_logs`

### 3. Activar Hooks

Edita o crea el archivo:

```
/tu-whmcs/includes/hooks/sunat_facturacion_hooks.php
```

Contenido:

```php
<?php
require_once __DIR__ . '/../../modules/addons/sunat_facturacion/hooks.php';
```

---

## Configuración

### Paso 1: Agregar Empresa SUNAT

1. Ve a **Addons → Facturación SUNAT Perú → Empresas**
2. Haz clic en **Nueva Empresa**
3. Completa los datos:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre Identificador** | Nombre para identificar la empresa en WHMCS | BLXKSTUDIO |
| **URL de la API** | URL de tu API SUNAT | https://api-sunat.blxkstudio.com |
| **Email API** | Email para login | admin@blxkstudio.com |
| **Password API** | Contraseña para login | Admin123456 |
| **Company ID** | ID de la empresa en la API | 1 |
| **Branch ID** | ID de la sucursal en la API | 1 |
| **Modo SUNAT** | Beta (pruebas) o Producción | Beta |
| **Serie Facturas** | Serie para facturas | F001 |
| **Serie Boletas** | Serie para boletas | B001 |
| **Emisión Automática** | ✅ Activar para emitir automáticamente | ✅ |
| **Emitir en Unpaid** | ✅ Emitir cuando pasa a Unpaid (además de Paid) | ✅ |
| **Activo** | ✅ Empresa activa | ✅ |

4. Haz clic en **Guardar Empresa**

### Paso 2: Configurar Tax ID en Clientes

Para que el sistema detecte automáticamente si debe emitir Factura o Boleta:

1. Edita tus clientes en WHMCS
2. En el campo **Tax ID**, ingresa:
   - **RUC (11 dígitos)**: Ejemplo `20123456789` → Emitirá **Factura**
   - **DNI (8 dígitos)**: Ejemplo `12345678` → Emitirá **Boleta**

El addon detecta automáticamente el tipo de documento según la longitud del Tax ID.

### Paso 3: Mapear Productos (Opcional)

Si quieres usar códigos SUNAT específicos para tus productos:

1. Ve a **Addons → Facturación SUNAT Perú → Mapeo Productos**
2. Haz clic en **Nuevo Mapeo**
3. Selecciona el producto WHMCS
4. Configura:
   - **Código SUNAT**: Ej. `81112200` (Servicios de hosting)
   - **Descripción**: Descripción para SUNAT
   - **Unidad**: NIU (unidad), ZZ (servicio), etc.
   - **Afectación IGV**: 10 (Gravado), 20 (Exonerado), 30 (Inafecto)

**Nota:** Los productos sin mapeo usarán automáticamente:
- Código: `PROD-{id}` (generado automáticamente)
- Descripción: Nombre del producto en WHMCS
- Unidad: ZZ (servicio)
- Afectación: 10 (Gravado)

---

## Uso

### Emisión Automática

Una vez configurado, el sistema emitirá automáticamente cuando:

1. **Invoice pasa de Draft → Unpaid** (si `emit_on_unpaid` está activado)
2. **Invoice es pagada** (pasa a Paid)

El proceso automático:
1. Detecta cambio de estado
2. Verifica Tax ID del cliente (RUC o DNI)
3. Obtiene items de la invoice
4. Crea comprobante en API SUNAT
5. Envía a SUNAT automáticamente
6. Guarda respuesta y archivos (XML, CDR)
7. Agrega nota en la invoice WHMCS

### Verificar Comprobantes

1. Ve a **Addons → Facturación SUNAT Perú → Comprobantes**
2. Filtra por estado: Todos, Pendientes, Rechazados
3. Descarga PDF, XML o CDR desde la tabla

También puedes ver el comprobante directamente en la invoice de WHMCS:
- Edita cualquier invoice
- Verás un banner arriba con el número de comprobante y estado SUNAT

### Logs del Sistema

Para debugging o auditoría:

1. Ve a **Addons → Facturación SUNAT Perú → Logs**
2. Verás todos los eventos: creación, envío, errores

---

## Flujo de Trabajo

```
┌──────────────────────┐
│ Admin crea Invoice   │
│ Estado: Draft        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Admin envía Invoice  │
│ Estado: Unpaid       │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────────────┐
│ Hook detecta cambio de estado    │
│ InvoiceChangeStatus              │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ ¿Tax ID del cliente?             │
│ · 11 dígitos → Factura (F001)    │
│ · 8 dígitos → Boleta (B001)      │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ Obtiene items y mapea productos  │
│ · Con mapeo → Usa config SUNAT   │
│ · Sin mapeo → Usa nombre producto│
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ POST /api/v1/invoices o boletas  │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ POST /{id}/send-sunat            │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ Guarda en mod_sunat_invoices     │
│ · estado_sunat                   │
│ · numero_completo                │
│ · xml_path, cdr_path, pdf_path   │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│ Agrega nota en Invoice WHMCS     │
│ "Factura F001-000002 - ACEPTADO" │
└───────────────────────────────────┘
```

---

## Estructura de Base de Datos

### mod_sunat_companies

Almacena las empresas SUNAT configuradas (soporte multi-empresa).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | ID único |
| company_name | varchar | Nombre identificador |
| api_url | varchar | URL de la API |
| api_email | varchar | Email login |
| api_password | varchar | Password (encriptado) |
| api_token | text | Token actual |
| company_id | int | ID en API SUNAT |
| branch_id | int | ID sucursal en API |
| modo | enum | beta/produccion |
| serie_factura | varchar | Serie facturas (F001) |
| serie_boleta | varchar | Serie boletas (B001) |
| auto_emit | boolean | Emisión automática |
| emit_on_unpaid | boolean | Emitir en Unpaid |
| active | boolean | Estado |

### mod_sunat_invoices

Almacena los comprobantes emitidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | ID único |
| whmcs_invoice_id | int | ID invoice WHMCS |
| sunat_company_id | int | Empresa emisora |
| sunat_invoice_id | int | ID en API SUNAT |
| tipo_documento | varchar | 01=Factura, 03=Boleta |
| numero_completo | varchar | F001-000001 |
| estado_sunat | enum | PENDIENTE/ACEPTADO/RECHAZADO |
| codigo_hash | varchar | Hash del comprobante |
| xml_path | varchar | Ruta del XML |
| cdr_path | varchar | Ruta del CDR |
| pdf_path | varchar | Ruta del PDF |
| respuesta_sunat | text | JSON respuesta |

### mod_sunat_product_mapping

Mapeo de productos WHMCS → SUNAT.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| whmcs_product_id | int | ID producto WHMCS |
| sunat_code | varchar | Código SUNAT |
| sunat_description | varchar | Descripción |
| sunat_unit | varchar | Unidad medida |
| sunat_tip_afe_igv | varchar | Tipo afectación IGV |

---

## Solución de Problemas

### Los comprobantes no se emiten automáticamente

1. Verifica que el addon esté activado
2. Verifica que los hooks estén cargados (`includes/hooks/sunat_facturacion_hooks.php`)
3. Verifica que la empresa tenga `auto_emit = 1`
4. Revisa los logs en **Addons → SUNAT → Logs**

### Error "No hay empresas SUNAT configuradas"

Agrega al menos una empresa en **Addons → SUNAT → Empresas**.

### Error de conexión con la API

1. Verifica que la URL de la API sea correcta
2. Verifica que las credenciales (email/password) sean correctas
3. Prueba el endpoint `/api/health` de tu API

### Comprobante rechazado por SUNAT

1. Ve a **Comprobantes** y busca el comprobante
2. Revisa el mensaje de error de SUNAT
3. Errores comunes:
   - **2800**: Tipo de documento no permitido (DNI en factura)
   - **2335**: El certificado no corresponde al RUC
   - Verifica que el cliente tenga Tax ID correcto (RUC o DNI)

---

## API Endpoints Utilizados

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/api/auth/login` | POST | Obtener token |
| `/api/v1/invoices` | POST | Crear factura |
| `/api/v1/boletas` | POST | Crear boleta |
| `/api/v1/invoices/{id}/send-sunat` | POST | Enviar a SUNAT |
| `/api/v1/invoices/{id}/download-pdf` | GET | Descargar PDF |
| `/api/v1/invoices/{id}/download-xml` | GET | Descargar XML |
| `/api/v1/invoices/{id}/download-cdr` | GET | Descargar CDR |

---

## Soporte

Para soporte técnico, reportar bugs o solicitar nuevas características:
- Email: support@blxkstudio.com
- GitHub: https://github.com/blxkstudio/facturacion-sunat-api

---

## Licencia

Copyright © 2025 BLXKSTUDIO
Todos los derechos reservados.

---

## Changelog

### v1.0.0 (2025-01-18)
- Versión inicial
- Soporte multi-empresa
- Emisión automática de facturas y boletas
- Detección inteligente RUC/DNI
- Mapeo de productos con fallback
- Panel de administración completo
- Sistema de logs
