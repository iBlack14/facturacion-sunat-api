# 📥 Instalación Rápida - Plugin WordPress SUNAT

## Descarga Directa

**Archivo:** `sunat-facturacion-electronica-v1.0.0.zip` (51 KB)

### Enlace de Descarga:

```
https://github.com/iBlack14/facturacion-sunat-api/raw/claude/deploy-configure-repo-01NHjUhiR72ovrrVgKmz96wL/wordpress-plugin/sunat-facturacion-electronica-v1.0.0.zip
```

## 🚀 Instalación en WordPress

### Método 1: Subir ZIP desde WordPress

1. Descarga el archivo ZIP usando el enlace de arriba
2. En WordPress, ve a **Plugins → Añadir nuevo**
3. Haz clic en **Subir plugin**
4. Selecciona el archivo ZIP descargado
5. Haz clic en **Instalar ahora**
6. Activa el plugin

### Método 2: FTP/SFTP

1. Descarga y descomprime el archivo ZIP
2. Sube la carpeta `sunat-facturacion-electronica` a `/wp-content/plugins/`
3. En WordPress, ve a **Plugins**
4. Activa **SUNAT Facturación Electrónica Perú**

## ⚙️ Configuración Inicial

### 1. Configurar API (Admin)

1. Ve a **SUNAT Facturación → Configuración**
2. Configura:
   - **URL API:** `https://api-sunat.blxkstudio.com`
   - **Email API:** Tu email registrado
   - **Contraseña API:** Tu contraseña
   - **Auto-emisión WooCommerce:** ✅ (si usas WooCommerce)
3. Guarda la configuración

### 2. Crear Página del Panel (Admin)

1. Ve a **Páginas → Añadir nueva**
2. Título: "Mi Panel de Facturación" (o el que prefieras)
3. En el contenido, agrega el shortcode:
   ```
   [sunat_panel]
   ```
4. Publica la página

### 3. Configurar Empresa (Usuario)

1. Ve a la página creada (Mi Panel de Facturación)
2. Haz clic en **Mi Empresa**
3. Completa todos los datos:
   - RUC
   - Razón Social
   - Dirección completa
   - Ubigeo
   - Modo: **Beta** (para pruebas) o **Producción**
   - Series: F001 (facturas), B001 (boletas)
4. Guarda

### 4. Subir Certificado Digital (Usuario)

1. En el panel, haz clic en **Certificado**
2. Selecciona tu archivo .pfx, .p12 o .pem
3. Ingresa la contraseña del certificado
4. Sube el certificado

### 5. ¡Listo para Facturar!

Ya puedes:
- Agregar clientes desde **Clientes**
- Emitir facturas desde **Nueva Factura**
- Ver historial en **Comprobantes**
- Si usas WooCommerce, las facturas se emiten automáticamente

## 📊 Características Incluidas

✅ Multi-usuario (cada usuario su propia empresa)
✅ Certificados digitales cifrados
✅ Facturas y Boletas automáticas
✅ Integración WooCommerce
✅ Gestión de clientes
✅ Descarga de PDFs
✅ Panel frontend completo
✅ Panel admin con estadísticas
✅ Logs del sistema
✅ Modo Beta/Producción
✅ Responsive (móvil)

## 🆘 Soporte

- **Email:** support@blxkstudio.com
- **Documentación:** Ver README.md en el plugin

---

**Versión:** 1.0.0
**Tamaño:** 51 KB
**Compatible:** WordPress 5.8+, PHP 7.4+
