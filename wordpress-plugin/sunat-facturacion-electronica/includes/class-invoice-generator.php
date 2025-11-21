<?php
/**
 * Generador de facturas y boletas
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Invoice_Generator {

    /**
     * Cliente API
     *
     * @since 1.0.0
     * @var Sunat_Facturacion_Api_Client
     */
    private $api_client;

    /**
     * Gestor de certificados
     *
     * @since 1.0.0
     * @var Sunat_Facturacion_Certificate_Manager
     */
    private $certificate_manager;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->api_client = new Sunat_Facturacion_Api_Client();
        $this->certificate_manager = new Sunat_Facturacion_Certificate_Manager();
    }

    /**
     * Generar y emitir factura/boleta
     *
     * @since 1.0.0
     * @param array $data Datos de la factura
     * @param int $user_id ID del usuario emisor
     * @return array
     */
    public function generate_and_emit($data, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Validar datos requeridos
        $validation = $this->validate_invoice_data($data);
        if (!$validation['success']) {
            return $validation;
        }

        // Obtener empresa del usuario
        $company = Sunat_Facturacion_Database::get_user_company($user_id);
        if (!$company) {
            return [
                'success' => false,
                'message' => 'No has configurado los datos de tu empresa'
            ];
        }

        // Verificar certificado activo
        $certificate = $this->certificate_manager->get_certificate_for_use($user_id);
        if (!$certificate) {
            return [
                'success' => false,
                'message' => 'No tienes un certificado digital activo'
            ];
        }

        // Determinar tipo de documento (Factura o Boleta)
        $tipo_documento = $this->determine_document_type($data['client']['tipo_documento'], $data['client']['numero_documento']);

        // Construir payload para API
        $payload = $this->build_invoice_payload($data, $company, $tipo_documento);

        // Login en API
        $login = $this->api_client->login();
        if (!$login['success']) {
            return [
                'success' => false,
                'message' => 'Error de autenticación con la API: ' . ($login['message'] ?? 'Sin detalles')
            ];
        }

        // Crear invoice/boleta en API
        if ($tipo_documento === '01') {
            $result = $this->api_client->create_invoice($payload);
        } else {
            $result = $this->api_client->create_boleta($payload);
        }

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Error al crear el comprobante: ' . ($result['message'] ?? 'Sin detalles')
            ];
        }

        $invoice_api_id = $result['data']['id'];

        // Subir certificado a la API
        $upload_result = $this->api_client->upload_certificate(
            $invoice_api_id,
            $certificate['filepath'],
            $certificate['password']
        );

        if (!$upload_result['success']) {
            return [
                'success' => false,
                'message' => 'Error al subir el certificado: ' . ($upload_result['message'] ?? 'Sin detalles')
            ];
        }

        // Enviar a SUNAT
        $sunat_result = $this->api_client->send_to_sunat($invoice_api_id);

        // Guardar en base de datos local
        $invoice_data = [
            'user_id' => $user_id,
            'api_invoice_id' => $invoice_api_id,
            'tipo_documento' => $tipo_documento,
            'serie' => $payload['serie'],
            'numero' => $payload['numero'],
            'fecha_emision' => $payload['fecha_emision'],
            'cliente_tipo_documento' => $data['client']['tipo_documento'],
            'cliente_numero_documento' => $data['client']['numero_documento'],
            'cliente_razon_social' => $data['client']['razon_social'],
            'moneda' => $data['moneda'] ?? 'PEN',
            'mto_oper_gravadas' => $payload['mto_oper_gravadas'],
            'mto_igv' => $payload['mto_igv'],
            'mto_imp_venta' => $payload['mto_imp_venta'],
            'estado_sunat' => $sunat_result['success'] ? 'ACEPTADO' : 'PENDIENTE',
            'mensaje_sunat' => $sunat_result['message'] ?? null,
            'created_at' => current_time('mysql')
        ];

        $invoice_id = Sunat_Facturacion_Database::save_invoice($invoice_data);

        // Guardar items
        if (isset($data['items']) && is_array($data['items'])) {
            $items_to_save = array_map(function($item) {
                return [
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'],
                    'unidad' => $item['unidad'],
                    'cantidad' => $item['cantidad'],
                    'mto_valor_unitario' => $item['mto_valor_unitario'],
                    'mto_igv' => $item['mto_igv'] ?? 0,
                    'mto_total' => $item['mto_total'] ?? 0
                ];
            }, $data['items']);

            Sunat_Facturacion_Database::save_invoice_items($invoice_id, $items_to_save);
        }

        // Log
        Sunat_Facturacion_Database::add_log([
            'user_id' => $user_id,
            'invoice_id' => $invoice_id,
            'action' => 'invoice_created',
            'level' => 'info',
            'message' => 'Comprobante creado: ' . $payload['serie'] . '-' . $payload['numero'],
            'created_at' => current_time('mysql')
        ]);

        return [
            'success' => true,
            'message' => $sunat_result['success'] ? 'Comprobante emitido y aceptado por SUNAT' : 'Comprobante creado (pendiente de respuesta SUNAT)',
            'invoice_id' => $invoice_id,
            'api_invoice_id' => $invoice_api_id,
            'serie' => $payload['serie'],
            'numero' => $payload['numero'],
            'estado_sunat' => $invoice_data['estado_sunat']
        ];
    }

    /**
     * Validar datos de factura
     *
     * @since 1.0.0
     * @param array $data
     * @return array
     */
    private function validate_invoice_data($data) {
        $required_fields = [
            'client' => 'Datos del cliente',
            'items' => 'Items de la factura'
        ];

        foreach ($required_fields as $field => $label) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'message' => "Falta el campo: {$label}"
                ];
            }
        }

        // Validar datos del cliente
        $required_client_fields = [
            'tipo_documento' => 'Tipo de documento',
            'numero_documento' => 'Número de documento',
            'razon_social' => 'Razón social / Nombre'
        ];

        foreach ($required_client_fields as $field => $label) {
            if (!isset($data['client'][$field]) || empty($data['client'][$field])) {
                return [
                    'success' => false,
                    'message' => "Falta el campo del cliente: {$label}"
                ];
            }
        }

        // Validar items
        if (empty($data['items']) || !is_array($data['items'])) {
            return [
                'success' => false,
                'message' => 'Debe incluir al menos un item'
            ];
        }

        return ['success' => true];
    }

    /**
     * Determinar tipo de documento
     *
     * @since 1.0.0
     * @param string $tipo_doc_cliente
     * @param string $numero_doc_cliente
     * @return string '01' para Factura, '03' para Boleta
     */
    private function determine_document_type($tipo_doc_cliente, $numero_doc_cliente) {
        // Si es RUC (6) o tiene 11 dígitos → Factura
        if ($tipo_doc_cliente === '6' || strlen($numero_doc_cliente) === 11) {
            return '01'; // Factura
        }

        // Si es DNI (1) o cualquier otro → Boleta
        return '03'; // Boleta
    }

    /**
     * Construir payload para API
     *
     * @since 1.0.0
     * @param array $data
     * @param object $company
     * @param string $tipo_documento
     * @return array
     */
    private function build_invoice_payload($data, $company, $tipo_documento) {
        // Obtener próximo número de serie
        $serie = $tipo_documento === '01' ? $company->serie_factura : $company->serie_boleta;
        $numero = $this->get_next_number($company->user_id, $serie);

        // Calcular totales
        $totals = $this->calculate_totals($data['items']);

        $payload = [
            // Datos del emisor
            'emisor' => [
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
                'nombre_comercial' => $company->nombre_comercial ?? $company->razon_social,
                'direccion' => $company->direccion ?? '',
                'ubigeo' => $company->ubigeo ?? '150101',
                'departamento' => $company->departamento ?? 'LIMA',
                'provincia' => $company->provincia ?? 'LIMA',
                'distrito' => $company->distrito ?? 'LIMA',
                'urbanizacion' => $company->urbanizacion ?? '-',
                'codigo_pais' => 'PE'
            ],

            // Datos del cliente
            'cliente' => [
                'tipo_documento' => $data['client']['tipo_documento'],
                'numero_documento' => $data['client']['numero_documento'],
                'razon_social' => $data['client']['razon_social'],
                'direccion' => $data['client']['direccion'] ?? '',
                'email' => $data['client']['email'] ?? ''
            ],

            // Datos del comprobante
            'tipo_documento' => $tipo_documento,
            'serie' => $serie,
            'numero' => $numero,
            'fecha_emision' => date('Y-m-d'),
            'hora_emision' => date('H:i:s'),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? date('Y-m-d'),

            // Moneda
            'moneda' => $data['moneda'] ?? 'PEN',

            // Totales
            'mto_oper_gravadas' => $totals['subtotal'],
            'mto_igv' => $totals['igv'],
            'mto_imp_venta' => $totals['total'],

            // Items
            'items' => array_map(function($item) {
                return [
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'],
                    'unidad' => $item['unidad'] ?? 'NIU',
                    'cantidad' => floatval($item['cantidad']),
                    'mto_valor_unitario' => floatval($item['mto_valor_unitario']),
                    'porcentaje_igv' => 18,
                    'igv' => round(floatval($item['mto_valor_unitario']) * 0.18, 2),
                    'tip_afe_igv' => $item['tip_afe_igv'] ?? '10',
                    'total_impuestos' => round(floatval($item['mto_valor_unitario']) * 0.18, 2),
                    'mto_precio_unitario' => round(floatval($item['mto_valor_unitario']) * 1.18, 2)
                ];
            }, $data['items'])
        ];

        // Agregar datos opcionales
        if (isset($data['observaciones'])) {
            $payload['observaciones'] = $data['observaciones'];
        }

        if (isset($data['orden_compra'])) {
            $payload['orden_compra'] = $data['orden_compra'];
        }

        return $payload;
    }

    /**
     * Calcular totales de la factura
     *
     * @since 1.0.0
     * @param array $items
     * @return array
     */
    private function calculate_totals($items) {
        $subtotal = 0;

        foreach ($items as $item) {
            $valor_unitario = floatval($item['mto_valor_unitario']);
            $cantidad = floatval($item['cantidad']);
            $subtotal += $valor_unitario * $cantidad;
        }

        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'igv' => $igv,
            'total' => $total
        ];
    }

    /**
     * Obtener próximo número de comprobante
     *
     * @since 1.0.0
     * @param int $user_id
     * @param string $serie
     * @return int
     */
    private function get_next_number($user_id, $serie) {
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_invoices';

        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(numero AS UNSIGNED)) FROM $table WHERE user_id = %d AND serie = %s",
            $user_id,
            $serie
        ));

        return $last_number ? intval($last_number) + 1 : 1;
    }

    /**
     * Descargar PDF de comprobante
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @param int $user_id
     * @return array
     */
    public function download_pdf($invoice_id, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Obtener invoice
        $invoice = Sunat_Facturacion_Database::get_invoice($invoice_id);

        if (!$invoice || $invoice->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Factura no encontrada'
            ];
        }

        // Login en API
        $login = $this->api_client->login();
        if (!$login['success']) {
            return [
                'success' => false,
                'message' => 'Error de autenticación'
            ];
        }

        // Descargar PDF
        return $this->api_client->download_pdf($invoice->api_invoice_id);
    }

    /**
     * Reenviar a SUNAT
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @param int $user_id
     * @return array
     */
    public function resend_to_sunat($invoice_id, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Obtener invoice
        $invoice = Sunat_Facturacion_Database::get_invoice($invoice_id);

        if (!$invoice || $invoice->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Factura no encontrada'
            ];
        }

        // Login en API
        $login = $this->api_client->login();
        if (!$login['success']) {
            return [
                'success' => false,
                'message' => 'Error de autenticación'
            ];
        }

        // Reenviar a SUNAT
        $result = $this->api_client->send_to_sunat($invoice->api_invoice_id);

        // Actualizar estado
        if ($result['success']) {
            Sunat_Facturacion_Database::save_invoice([
                'id' => $invoice_id,
                'estado_sunat' => 'ACEPTADO',
                'mensaje_sunat' => $result['message'] ?? 'Aceptado'
            ]);
        }

        return $result;
    }

    /**
     * Anular comprobante
     *
     * @since 1.0.0
     * @param int $invoice_id
     * @param string $motivo
     * @param int $user_id
     * @return array
     */
    public function void_invoice($invoice_id, $motivo, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Obtener invoice
        $invoice = Sunat_Facturacion_Database::get_invoice($invoice_id);

        if (!$invoice || $invoice->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Factura no encontrada'
            ];
        }

        // Login en API
        $login = $this->api_client->login();
        if (!$login['success']) {
            return [
                'success' => false,
                'message' => 'Error de autenticación'
            ];
        }

        // Crear comunicación de baja
        $result = $this->api_client->void_document($invoice->api_invoice_id, [
            'fecha_emision' => date('Y-m-d'),
            'motivo' => $motivo
        ]);

        if ($result['success']) {
            // Actualizar estado
            Sunat_Facturacion_Database::save_invoice([
                'id' => $invoice_id,
                'estado_sunat' => 'ANULADO',
                'mensaje_sunat' => 'Anulado: ' . $motivo
            ]);

            // Log
            Sunat_Facturacion_Database::add_log([
                'user_id' => $user_id,
                'invoice_id' => $invoice_id,
                'action' => 'invoice_voided',
                'level' => 'warning',
                'message' => 'Comprobante anulado: ' . $motivo,
                'created_at' => current_time('mysql')
            ]);
        }

        return $result;
    }
}
