<?php
/**
 * Clase principal del plugin
 *
 * @since 1.0.0
 */
class Sunat_Facturacion {

    /**
     * Loader del plugin
     *
     * @since 1.0.0
     * @var Sunat_Facturacion_Loader
     */
    protected $loader;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_woocommerce_hooks();
    }

    /**
     * Cargar dependencias
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Loader
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-loader.php';

        // Database
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-database.php';

        // API Client
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-api-client.php';

        // Certificate Manager
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-certificate-manager.php';

        // Invoice Generator
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-invoice-generator.php';

        // Admin
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'admin/class-admin.php';

        // Public
        require_once SUNAT_FACTURACION_PLUGIN_DIR . 'public/class-public.php';

        // WooCommerce Integration
        if (class_exists('WooCommerce')) {
            require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        }

        $this->loader = new Sunat_Facturacion_Loader();
    }

    /**
     * Configurar localización
     *
     * @since 1.0.0
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'sunat-facturacion',
                false,
                dirname(SUNAT_FACTURACION_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Definir hooks del admin
     *
     * @since 1.0.0
     */
    private function define_admin_hooks() {
        $plugin_admin = new Sunat_Facturacion_Admin();

        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }

    /**
     * Definir hooks del frontend
     *
     * @since 1.0.0
     */
    private function define_public_hooks() {
        $plugin_public = new Sunat_Facturacion_Public();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_filter('query_vars', $plugin_public, 'add_query_vars');
        $this->loader->add_action('template_redirect', $plugin_public, 'template_redirect');
    }

    /**
     * Definir hooks de WooCommerce
     *
     * @since 1.0.0
     */
    private function define_woocommerce_hooks() {
        if (class_exists('WooCommerce')) {
            $woo_integration = new Sunat_Facturacion_WooCommerce();

            $this->loader->add_action('woocommerce_order_status_completed', $woo_integration, 'emit_invoice_on_complete', 10, 1);
            $this->loader->add_action('woocommerce_checkout_fields', $woo_integration, 'add_billing_fields');
            $this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $woo_integration, 'display_sunat_invoice_info');
        }
    }

    /**
     * Ejecutar el plugin
     *
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}
