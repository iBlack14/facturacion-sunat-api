<?php
/**
 * Panel de administración
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Admin {

    /**
     * ID del plugin
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_name;

    /**
     * Versión del plugin
     *
     * @since 1.0.0
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registrar menú de admin
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            'SUNAT Facturación',
            'SUNAT Facturación',
            'manage_options',
            'sunat-facturacion',
            [$this, 'render_settings_page'],
            'dashicons-media-text',
            26
        );

        add_submenu_page(
            'sunat-facturacion',
            'Configuración',
            'Configuración',
            'manage_options',
            'sunat-facturacion',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'sunat-facturacion',
            'Estadísticas',
            'Estadísticas',
            'manage_options',
            'sunat-facturacion-stats',
            [$this, 'render_stats_page']
        );

        add_submenu_page(
            'sunat-facturacion',
            'Logs del Sistema',
            'Logs',
            'manage_options',
            'sunat-facturacion-logs',
            [$this, 'render_logs_page']
        );
    }

    /**
     * Renderizar página de configuración
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        // Guardar configuración
        if (isset($_POST['sunat_save_settings']) && check_admin_referer('sunat_settings')) {
            update_option('sunat_api_url', sanitize_text_field($_POST['api_url']));
            update_option('sunat_api_email', sanitize_email($_POST['api_email']));

            if (!empty($_POST['api_password'])) {
                update_option('sunat_api_password', sanitize_text_field($_POST['api_password']));
            }

            update_option('sunat_auto_emit_woocommerce', isset($_POST['auto_emit_woocommerce']) ? '1' : '0');

            echo '<div class="notice notice-success"><p>Configuración guardada correctamente</p></div>';
        }

        // Obtener configuración actual
        $api_url = get_option('sunat_api_url', 'https://api-sunat.blxkstudio.com');
        $api_email = get_option('sunat_api_email', '');
        $auto_emit = get_option('sunat_auto_emit_woocommerce', '1');

        include_once plugin_dir_path(__FILE__) . 'partials/settings.php';
    }

    /**
     * Renderizar página de estadísticas
     *
     * @since 1.0.0
     */
    public function render_stats_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoices';

        // Estadísticas generales
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $aceptados = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE estado_sunat = 'ACEPTADO'");
        $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE estado_sunat = 'PENDIENTE'");
        $rechazados = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE estado_sunat = 'RECHAZADO'");

        // Estadísticas por usuario
        $stats_by_user = $wpdb->get_results("
            SELECT
                u.display_name,
                COUNT(*) as total,
                SUM(CASE WHEN i.estado_sunat = 'ACEPTADO' THEN 1 ELSE 0 END) as aceptados
            FROM $table i
            JOIN {$wpdb->users} u ON i.user_id = u.ID
            GROUP BY i.user_id
            ORDER BY total DESC
            LIMIT 10
        ");

        // Estadísticas por mes (últimos 6 meses)
        $stats_by_month = $wpdb->get_results("
            SELECT
                DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
                COUNT(*) as total,
                SUM(mto_imp_venta) as monto_total
            FROM $table
            WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
            ORDER BY mes DESC
        ");

        include_once plugin_dir_path(__FILE__) . 'partials/stats.php';
    }

    /**
     * Renderizar página de logs
     *
     * @since 1.0.0
     */
    public function render_logs_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_logs';

        // Paginación
        $per_page = 50;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        // Filtros
        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['level'])) {
            $where[] = 'level = %s';
            $params[] = sanitize_text_field($_GET['level']);
        }

        if (!empty($_GET['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = intval($_GET['user_id']);
        }

        $where_sql = implode(' AND ', $where);

        // Total de registros
        $total_items = $wpdb->get_var(
            empty($params) ?
            "SELECT COUNT(*) FROM $table WHERE $where_sql" :
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", $params)
        );

        // Obtener logs
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, u.display_name
                FROM $table l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                WHERE $where_sql
                ORDER BY l.created_at DESC
                LIMIT %d OFFSET %d",
                array_merge($params, [$per_page, $offset])
            )
        );

        $total_pages = ceil($total_items / $per_page);

        include_once plugin_dir_path(__FILE__) . 'partials/logs.php';
    }

    /**
     * Registrar estilos del admin
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/admin.css',
            [],
            $this->version
        );
    }

    /**
     * Registrar scripts del admin
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/admin.js',
            ['jquery'],
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'sunatAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sunat_admin_nonce')
        ]);
    }

    /**
     * Agregar enlace de configuración en la lista de plugins
     *
     * @since 1.0.0
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=sunat-facturacion') . '">Configuración</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
