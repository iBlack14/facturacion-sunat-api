<?php
/**
 * Clase para manejo de base de datos
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Database {

    /**
     * Obtener empresa del usuario actual
     *
     * @since 1.0.0
     * @param int $user_id
     * @return object|null
     */
    public static function get_user_company($user_id = null) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_companies';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Crear o actualizar empresa
     *
     * @since 1.0.0
     * @param int $user_id
     * @param array $data
     * @return int|bool
     */
    public static function save_company($user_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_companies';

        $existing = self::get_user_company($user_id);

        if ($existing) {
            // Actualizar
            return $wpdb->update(
                $table,
                $data,
                ['user_id' => $user_id],
                null,
                ['%d']
            );
        } else {
            // Crear
            $data['user_id'] = $user_id;
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Obtener clientes del usuario
     *
     * @since 1.0.0
     * @param int $user_id
     * @return array
     */
    public static function get_user_clients($user_id = null) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_clients';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND active = 1 ORDER BY created_at DESC",
            $user_id
        ));
    }

    /**
     * Obtener cliente por ID
     *
     * @since 1.0.0
     * @param int $client_id
     * @return object|null
     */
    public static function get_client($client_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_clients';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $client_id
        ));
    }

    /**
     * Guardar cliente
     *
     * @since 1.0.0
     * @param array $data
     * @return int|bool
     */
    public static function save_client($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_clients';

        if (isset($data['id']) && $data['id'] > 0) {
            $id = $data['id'];
            unset($data['id']);
            $wpdb->update($table, $data, ['id' => $id]);
            return $id;
        } else {
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Obtener facturas del usuario
     *
     * @since 1.0.0
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public static function get_user_invoices($user_id = null, $args = []) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_invoices';

        $where = ["user_id = %d"];
        $values = [$user_id];

        if (!empty($args['estado_sunat'])) {
            $where[] = "estado_sunat = %s";
            $values[] = $args['estado_sunat'];
        }

        if (!empty($args['tipo_documento'])) {
            $where[] = "tipo_documento = %s";
            $values[] = $args['tipo_documento'];
        }

        $limit = isset($args['limit']) ? intval($args['limit']) : 50;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Obtener factura por ID
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @return object|null
     */
    public static function get_invoice($invoice_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoices';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $invoice_id
        ));
    }

    /**
     * Guardar factura
     *
     * @since 1.0.0
     * @param array $data
     * @return int|bool
     */
    public static function save_invoice($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoices';

        if (isset($data['id']) && $data['id'] > 0) {
            $id = $data['id'];
            unset($data['id']);
            $wpdb->update($table, $data, ['id' => $id]);
            return $id;
        } else {
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Guardar items de factura
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @param array $items
     * @return bool
     */
    public static function save_invoice_items($invoice_id, $items) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoice_items';

        // Eliminar items existentes
        $wpdb->delete($table, ['invoice_id' => $invoice_id]);

        // Insertar nuevos items
        foreach ($items as $item) {
            $item['invoice_id'] = $invoice_id;
            $wpdb->insert($table, $item);
        }

        return true;
    }

    /**
     * Obtener items de factura
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @return array
     */
    public static function get_invoice_items($invoice_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoice_items';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE invoice_id = %d",
            $invoice_id
        ));
    }

    /**
     * Obtener certificado activo del usuario
     *
     * @since 1.0.0
     * @param int $user_id
     * @return object|null
     */
    public static function get_user_certificate($user_id = null) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_certificates';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND active = 1 ORDER BY uploaded_at DESC LIMIT 1",
            $user_id
        ));
    }

    /**
     * Guardar certificado
     *
     * @since 1.0.0
     * @param array $data
     * @return int|bool
     */
    public static function save_certificate($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_certificates';

        // Desactivar certificados anteriores del usuario
        if (isset($data['user_id'])) {
            $wpdb->update(
                $table,
                ['active' => 0],
                ['user_id' => $data['user_id']]
            );
        }

        // Insertar nuevo certificado
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    /**
     * Agregar log
     *
     * @since 1.0.0
     * @param array $data
     * @return int|bool
     */
    public static function add_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_logs';

        return $wpdb->insert($table, $data);
    }

    /**
     * Obtener estadísticas del usuario
     *
     * @since 1.0.0
     * @param int $user_id
     * @return array
     */
    public static function get_user_stats($user_id = null) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_invoices';

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));

        $aceptados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND estado_sunat = 'ACEPTADO'",
            $user_id
        ));

        $pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND estado_sunat = 'PENDIENTE'",
            $user_id
        ));

        $rechazados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND estado_sunat = 'RECHAZADO'",
            $user_id
        ));

        return [
            'total' => intval($total),
            'aceptados' => intval($aceptados),
            'pendientes' => intval($pendientes),
            'rechazados' => intval($rechazados)
        ];
    }
}
