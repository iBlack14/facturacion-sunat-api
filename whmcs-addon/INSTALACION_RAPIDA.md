# 🚀 Instalación Rápida - Addon WHMCS SUNAT

## 📦 Archivo incluido

- **sunat_facturacion_v1.0.0.zip** (19 KB)

---

## ⚡ Instalación en 5 pasos

### 1. Descargar el ZIP

Descarga el archivo `sunat_facturacion_v1.0.0.zip` del repositorio.

### 2. Extraer en WHMCS

Descomprime el ZIP **dentro de** la carpeta `modules/addons/` de tu WHMCS:

```bash
# En tu servidor WHMCS:
cd /path/to/whmcs/modules/addons/
unzip sunat_facturacion_v1.0.0.zip
```

La estructura final debe ser:
```
/path/to/whmcs/
└── modules/
    └── addons/
        └── sunat_facturacion/    ← El addon descomprimido aquí
            ├── sunat_facturacion.php
            ├── hooks.php
            ├── lib/
            └── templates/
```

### 3. Activar el módulo en WHMCS

1. Ingresa a tu panel admin de WHMCS
2. Ve a **Setup → Addon Modules**
3. Busca "**Facturación SUNAT Perú**"
4. Haz clic en **Activate**
5. Marca los roles que tendrán acceso (Full Administrator recomendado)

### 4. Activar los Hooks

Crea el archivo `/path/to/whmcs/includes/hooks/sunat_facturacion_hooks.php`:

```php
<?php
require_once __DIR__ . '/../../modules/addons/sunat_facturacion/hooks.php';
```

O si prefieres, por FTP/SSH:

```bash
cd /path/to/whmcs/includes/hooks/
nano sunat_facturacion_hooks.php
```

Pega el contenido y guarda.

### 5. Configurar tu primera empresa

1. Ve a **Addons → Facturación SUNAT Perú**
2. Clic en pestaña **"Empresas"**
3. Clic en **"Nueva Empresa"**
4. Completa los datos:

```
Nombre Identificador: BLXKSTUDIO
URL de la API: https://api-sunat.blxkstudio.com
Email API: admin@blxkstudio.com
Password API: Tu password
Company ID: 1
Branch ID: 1
Modo SUNAT: Beta (para pruebas)
Serie Facturas: F001
Serie Boletas: B001
☑ Emisión Automática
☑ Emitir en Unpaid
☑ Activo
```

5. Haz clic en **"Guardar Empresa"**

---

## ✅ ¡Listo!

Ahora cada vez que:
- Una invoice pasa a **Unpaid** (factura sin pagar)
- Una invoice pasa a **Paid** (factura pagada)

El sistema automáticamente:
1. Detecta el Tax ID del cliente (RUC o DNI)
2. Crea la Factura (RUC) o Boleta (DNI) en la API SUNAT
3. La envía a SUNAT Beta/Producción
4. Guarda el XML, CDR y puede generar PDF
5. Agrega una nota en la invoice con el número del comprobante

---

## 📋 Configuración de Clientes

Para que funcione correctamente, tus clientes deben tener configurado el **Tax ID**:

### En WHMCS:
1. Ve a **Clients → View/Search Clients**
2. Edita un cliente
3. En el campo **"Tax ID"** ingresa:
   - **RUC (11 dígitos)**: `20123456789` → Emitirá **Factura**
   - **DNI (8 dígitos)**: `12345678` → Emitirá **Boleta**

---

## 🔧 Configuraciones Opcionales

### Mapeo de Productos (Opcional)

Si quieres usar códigos SUNAT específicos:

1. Ve a **Addons → Facturación SUNAT Perú → Mapeo Productos**
2. Haz clic en **"Nuevo Mapeo"**
3. Asigna códigos SUNAT a tus productos

**Si NO mapeas productos**, el sistema usará automáticamente:
- Código: `PROD-{id}` (ej: PROD-5)
- Descripción: Nombre del producto en WHMCS
- Unidad: ZZ (Servicio)
- Afectación IGV: 10 (Gravado)

---

## 📊 Verificar Comprobantes

Después de crear una invoice:

1. Ve a **Addons → Facturación SUNAT Perú → Comprobantes**
2. Verás todos los comprobantes emitidos
3. Puedes filtrar por: Todos / Pendientes / Rechazados
4. Descarga PDF, XML o CDR desde la tabla

---

## ❓ Soporte

- **Documentación completa**: Ver `README.md`
- **Logs del sistema**: Addons → SUNAT → Logs
- **GitHub**: https://github.com/blxkstudio/facturacion-sunat-api

---

## 🎉 ¡Ahora tienes facturación electrónica automática en WHMCS!
