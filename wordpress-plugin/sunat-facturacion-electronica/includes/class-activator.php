<?php
/**
 * Activador del plugin
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Activator {

    /**
     * Ejecutar durante la activación del plugin
     *
     * @since 1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::create_upload_directory();
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crear tablas de base de datos
     *
     * @since 1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de empresas (una por usuario WordPress)
        $table_companies = $wpdb->prefix . 'sunat_companies';
        $sql_companies = "CREATE TABLE IF NOT EXISTS $table_companies (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            ruc varchar(11) NOT NULL,
            razon_social varchar(255) NOT NULL,
            nombre_comercial varchar(255) DEFAULT NULL,
            direccion text DEFAULT NULL,
            ubigeo varchar(6) DEFAULT NULL,
            distrito varchar(100) DEFAULT NULL,
            provincia varchar(100) DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            telefono varchar(20) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            logo_url varchar(255) DEFAULT NULL,
            usuario_sol varchar(100) DEFAULT NULL,
            clave_sol varchar(255) DEFAULT NULL,
            api_token text DEFAULT NULL,
            modo enum('beta','produccion') DEFAULT 'beta',
            serie_factura varchar(10) DEFAULT 'F001',
            serie_boleta varchar(10) DEFAULT 'B001',
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY ruc (ruc)
        ) $charset_collate;";

        // Tabla de certificados digitales
        $table_certificates = $wpdb->prefix . 'sunat_certificates';
        $sql_certificates = "CREATE TABLE IF NOT EXISTS $table_certificates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_id bigint(20) NOT NULL,
            certificate_path varchar(255) NOT NULL,
            certificate_password varchar(255) NOT NULL,
            certificate_type enum('pfx','pem') NOT NULL,
            valid_from date DEFAULT NULL,
            valid_to date DEFAULT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY company_id (company_id)
        ) $charset_collate;";

        // Tabla de clientes (por usuario)
        $table_clients = $wpdb->prefix . 'sunat_clients';
        $sql_clients = "CREATE TABLE IF NOT EXISTS $table_clients (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_id bigint(20) NOT NULL,
            tipo_documento varchar(2) NOT NULL,
            numero_documento varchar(20) NOT NULL,
            razon_social varchar(255) NOT NULL,
            nombre_comercial varchar(255) DEFAULT NULL,
            direccion text DEFAULT NULL,
            ubigeo varchar(6) DEFAULT NULL,
            distrito varchar(100) DEFAULT NULL,
            provincia varchar(100) DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            telefono varchar(20) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY company_id (company_id),
            KEY numero_documento (numero_documento)
        ) $charset_collate;";

        // Tabla de facturas/boletas emitidas
        $table_invoices = $wpdb->prefix . 'sunat_invoices';
        $sql_invoices = "CREATE TABLE IF NOT EXISTS $table_invoices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_id bigint(20) NOT NULL,
            client_id bigint(20) DEFAULT NULL,
            woo_order_id bigint(20) DEFAULT NULL,
            api_invoice_id bigint(20) DEFAULT NULL,
            tipo_documento varchar(10) NOT NULL,
            serie varchar(10) NOT NULL,
            correlativo varchar(20) DEFAULT NULL,
            numero_completo varchar(30) DEFAULT NULL,
            fecha_emision date NOT NULL,
            moneda varchar(3) DEFAULT 'PEN',
            total decimal(10,2) NOT NULL,
            igv decimal(10,2) DEFAULT 0,
            subtotal decimal(10,2) DEFAULT 0,
            estado_sunat enum('PENDIENTE','ACEPTADO','RECHAZADO','ERROR') DEFAULT 'PENDIENTE',
            codigo_hash varchar(100) DEFAULT NULL,
            xml_path varchar(255) DEFAULT NULL,
            cdr_path varchar(255) DEFAULT NULL,
            pdf_path varchar(255) DEFAULT NULL,
            respuesta_sunat text DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY company_id (company_id),
            KEY client_id (client_id),
            KEY woo_order_id (woo_order_id),
            KEY estado_sunat (estado_sunat),
            KEY numero_completo (numero_completo)
        ) $charset_collate;";

        // Tabla de items de facturas
        $table_invoice_items = $wpdb->prefix . 'sunat_invoice_items';
        $sql_invoice_items = "CREATE TABLE IF NOT EXISTS $table_invoice_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            codigo varchar(50) NOT NULL,
            descripcion varchar(255) NOT NULL,
            unidad varchar(10) DEFAULT 'NIU',
            cantidad decimal(10,2) NOT NULL,
            precio_unitario decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            igv decimal(10,2) DEFAULT 0,
            total decimal(10,2) NOT NULL,
            tipo_afectacion varchar(2) DEFAULT '10',
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id)
        ) $charset_collate;";

        // Tabla de logs
        $table_logs = $wpdb->prefix . 'sunat_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            invoice_id bigint(20) DEFAULT NULL,
            action varchar(50) NOT NULL,
            level enum('info','warning','error') DEFAULT 'info',
            message text NOT NULL,
            data text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY invoice_id (invoice_id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_companies);
        dbDelta($sql_certificates);
        dbDelta($sql_clients);
        dbDelta($sql_invoices);
        dbDelta($sql_invoice_items);
        dbDelta($sql_logs);
    }

    /**
     * Crear directorio de uploads
     *
     * @since 1.0.0
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $sunat_dir = $upload_dir['basedir'] . '/sunat-facturacion';

        if (!file_exists($sunat_dir)) {
            wp_mkdir_p($sunat_dir);
            wp_mkdir_p($sunat_dir . '/certificates');
            wp_mkdir_p($sunat_dir . '/xml');
            wp_mkdir_p($sunat_dir . '/cdr');
            wp_mkdir_p($sunat_dir . '/pdf');

            // Crear .htaccess para proteger archivos
            $htaccess_content = "deny from all";
            file_put_contents($sunat_dir . '/.htaccess', $htaccess_content);
        }
    }

    /**
     * Configurar opciones por defecto
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        add_option('sunat_facturacion_version', SUNAT_FACTURACION_VERSION);
        add_option('sunat_facturacion_api_url', 'https://api-sunat.blxkstudio.com');
        add_option('sunat_facturacion_api_email', '');
        add_option('sunat_facturacion_api_password', '');
        add_option('sunat_facturacion_woo_auto_emit', 'yes');
        add_option('sunat_facturacion_woo_emit_on_status', 'completed');
    }
}
