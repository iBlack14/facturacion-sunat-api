<?php
/**
 * Desactivador del plugin
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Deactivator {

    /**
     * Ejecutar durante la desactivación del plugin
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // NO eliminar tablas ni datos
        // Solo limpiar transientes si es necesario
        delete_transient('sunat_facturacion_api_status');
    }
}
