<?php
/**
 * Plugin Name: SUNAT Facturación Electrónica Perú
 * Plugin URI: https://blxkstudio.com/sunat-facturacion
 * Description: Sistema completo de facturación electrónica SUNAT para WordPress. Multi-usuario, integración WooCommerce, certificados digitales por usuario.
 * Version: 1.0.0
 * Author: BLXKSTUDIO
 * Author URI: https://blxkstudio.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sunat-facturacion
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Si se accede directamente, abortar
if (!defined('WPINC')) {
    die;
}

// Constantes del plugin
define('SUNAT_FACTURACION_VERSION', '1.0.0');
define('SUNAT_FACTURACION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUNAT_FACTURACION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SUNAT_FACTURACION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin
 */
function activate_sunat_facturacion() {
    require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-activator.php';
    Sunat_Facturacion_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin
 */
function deactivate_sunat_facturacion() {
    require_once SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-deactivator.php';
    Sunat_Facturacion_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sunat_facturacion');
register_deactivation_hook(__FILE__, 'deactivate_sunat_facturacion');

/**
 * Clase principal del plugin
 */
require SUNAT_FACTURACION_PLUGIN_DIR . 'includes/class-sunat-facturacion.php';

/**
 * Iniciar el plugin
 */
function run_sunat_facturacion() {
    $plugin = new Sunat_Facturacion();
    $plugin->run();
}
run_sunat_facturacion();
