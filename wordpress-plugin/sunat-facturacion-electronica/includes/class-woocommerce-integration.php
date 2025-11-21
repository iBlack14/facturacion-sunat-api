<?php
/**
 * Integración con WooCommerce
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_WooCommerce {

    /**
     * Generador de facturas
     *
     * @since 1.0.0
     * @var Sunat_Facturacion_Invoice_Generator
     */
    private $invoice_generator;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->invoice_generator = new Sunat_Facturacion_Invoice_Generator();
    }

    /**
     * Agregar campos personalizados al checkout
     *
     * @since 1.0.0
     * @param array $fields
     * @return array
     */
    public function add_billing_fields($fields) {
        // Agregar tipo de documento
        $fields['billing']['billing_document_type'] = [
            'type' => 'select',
            'label' => 'Tipo de Documento',
            'required' => true,
            'class' => ['form-row-wide'],
            'priority' => 25,
            'options' => [
                '' => 'Seleccionar...',
                '1' => 'DNI',
                '6' => 'RUC',
                '4' => 'Carnet de Extranjería',
                '7' => 'Pasaporte'
            ]
        ];

        // Agregar número de documento
        $fields['billing']['billing_document_number'] = [
            'type' => 'text',
            'label' => 'Número de Documento',
            'required' => true,
            'class' => ['form-row-wide'],
            'priority' => 26,
            'placeholder' => 'Ingrese su DNI, RUC o documento'
        ];

        // Modificar campo de empresa (opcional para RUC)
        if (isset($fields['billing']['billing_company'])) {
            $fields['billing']['billing_company']['priority'] = 27;
            $fields['billing']['billing_company']['label'] = 'Razón Social / Empresa (solo para RUC)';
            $fields['billing']['billing_company']['required'] = false;
        }

        return $fields;
    }

    /**
     * Validar campos personalizados en checkout
     *
     * @since 1.0.0
     */
    public function validate_checkout_fields() {
        $document_type = isset($_POST['billing_document_type']) ? sanitize_text_field($_POST['billing_document_type']) : '';
        $document_number = isset($_POST['billing_document_number']) ? sanitize_text_field($_POST['billing_document_number']) : '';

        if (empty($document_type)) {
            wc_add_notice('Por favor seleccione el tipo de documento', 'error');
        }

        if (empty($document_number)) {
            wc_add_notice('Por favor ingrese el número de documento', 'error');
        }

        // Validar formato según tipo
        if ($document_type === '1' && strlen($document_number) !== 8) {
            wc_add_notice('El DNI debe tener 8 dígitos', 'error');
        }

        if ($document_type === '6') {
            if (strlen($document_number) !== 11) {
                wc_add_notice('El RUC debe tener 11 dígitos', 'error');
            }
            // Si es RUC, la razón social es obligatoria
            $company = isset($_POST['billing_company']) ? sanitize_text_field($_POST['billing_company']) : '';
            if (empty($company)) {
                wc_add_notice('La Razón Social es obligatoria para RUC', 'error');
            }
        }
    }

    /**
     * Guardar campos personalizados en el pedido
     *
     * @since 1.0.0
     * @param int $order_id
     */
    public function save_checkout_fields($order_id) {
        if (isset($_POST['billing_document_type'])) {
            update_post_meta($order_id, '_billing_document_type', sanitize_text_field($_POST['billing_document_type']));
        }

        if (isset($_POST['billing_document_number'])) {
            update_post_meta($order_id, '_billing_document_number', sanitize_text_field($_POST['billing_document_number']));
        }
    }

    /**
     * Mostrar campos personalizados en admin de pedido
     *
     * @since 1.0.0
     * @param object $order
     */
    public function display_admin_order_meta($order) {
        $document_type = get_post_meta($order->get_id(), '_billing_document_type', true);
        $document_number = get_post_meta($order->get_id(), '_billing_document_number', true);

        if ($document_type && $document_number) {
            $doc_types = [
                '1' => 'DNI',
                '6' => 'RUC',
                '4' => 'Carnet de Extranjería',
                '7' => 'Pasaporte'
            ];

            echo '<div class="order_data_column">';
            echo '<h3>Datos de Facturación SUNAT</h3>';
            echo '<p><strong>Tipo:</strong> ' . esc_html($doc_types[$document_type] ?? $document_type) . '</p>';
            echo '<p><strong>Número:</strong> ' . esc_html($document_number) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Emitir factura/boleta automáticamente al completar pedido
     *
     * @since 1.0.0
     * @param int $order_id
     */
    public function emit_invoice_on_complete($order_id) {
        // Obtener pedido
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Verificar si ya se emitió comprobante
        $invoice_id = get_post_meta($order_id, '_sunat_invoice_id', true);
        if ($invoice_id) {
            return; // Ya se emitió
        }

        // Obtener owner del sitio (el que vende)
        $owner_id = $this->get_store_owner_id();

        if (!$owner_id) {
            $this->log_woocommerce_error($order_id, 'No se encontró el propietario de la tienda configurado');
            return;
        }

        // Verificar que el propietario tenga empresa y certificado configurado
        $company = Sunat_Facturacion_Database::get_user_company($owner_id);
        if (!$company) {
            $this->log_woocommerce_error($order_id, 'El propietario no tiene empresa configurada');
            return;
        }

        $certificate_manager = new Sunat_Facturacion_Certificate_Manager();
        $certificate = $certificate_manager->get_active_certificate($owner_id);
        if (!$certificate) {
            $this->log_woocommerce_error($order_id, 'El propietario no tiene certificado activo');
            return;
        }

        // Construir datos del cliente
        $document_type = get_post_meta($order_id, '_billing_document_type', true);
        $document_number = get_post_meta($order_id, '_billing_document_number', true);

        if (!$document_type || !$document_number) {
            $this->log_woocommerce_error($order_id, 'Faltan datos de documento del cliente');
            return;
        }

        // Determinar razón social
        $razon_social = '';
        if ($document_type === '6') {
            // Para RUC, usar billing_company
            $razon_social = $order->get_billing_company();
        }

        if (empty($razon_social)) {
            // Para DNI u otros, usar nombre completo
            $razon_social = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }

        $client_data = [
            'tipo_documento' => $document_type,
            'numero_documento' => $document_number,
            'razon_social' => $razon_social,
            'direccion' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
            'email' => $order->get_billing_email()
        ];

        // Construir items
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            $subtotal = $item->get_subtotal(); // Sin impuestos
            $unit_price = $subtotal / $quantity;

            $items[] = [
                'codigo' => $product ? $product->get_sku() : 'PROD-' . $item->get_product_id(),
                'descripcion' => $item->get_name(),
                'unidad' => 'NIU',
                'cantidad' => $quantity,
                'mto_valor_unitario' => round($unit_price, 2),
                'tip_afe_igv' => '10' // Gravado por defecto
            ];
        }

        // Agregar shipping como item si existe
        if ($order->get_shipping_total() > 0) {
            $items[] = [
                'codigo' => 'SHIPPING',
                'descripcion' => 'Envío: ' . $order->get_shipping_method(),
                'unidad' => 'ZZ',
                'cantidad' => 1,
                'mto_valor_unitario' => round($order->get_shipping_total(), 2),
                'tip_afe_igv' => '10'
            ];
        }

        // Construir datos de factura
        $invoice_data = [
            'client' => $client_data,
            'items' => $items,
            'moneda' => $order->get_currency(),
            'observaciones' => 'Pedido WooCommerce #' . $order->get_order_number()
        ];

        // Emitir factura/boleta
        $result = $this->invoice_generator->generate_and_emit($invoice_data, $owner_id);

        if ($result['success']) {
            // Guardar ID de invoice en el pedido
            update_post_meta($order_id, '_sunat_invoice_id', $result['invoice_id']);
            update_post_meta($order_id, '_sunat_serie', $result['serie']);
            update_post_meta($order_id, '_sunat_numero', $result['numero']);
            update_post_meta($order_id, '_sunat_estado', $result['estado_sunat']);

            // Agregar nota al pedido
            $order->add_order_note(
                sprintf(
                    'Comprobante SUNAT emitido: %s-%s (Estado: %s)',
                    $result['serie'],
                    $result['numero'],
                    $result['estado_sunat']
                )
            );

            // Log
            Sunat_Facturacion_Database::add_log([
                'user_id' => $owner_id,
                'invoice_id' => $result['invoice_id'],
                'action' => 'woocommerce_invoice_auto_emit',
                'level' => 'info',
                'message' => 'Comprobante emitido automáticamente para pedido WooCommerce #' . $order->get_order_number(),
                'created_at' => current_time('mysql')
            ]);

        } else {
            // Log error
            $this->log_woocommerce_error($order_id, $result['message']);

            // Agregar nota al pedido
            $order->add_order_note(
                'Error al emitir comprobante SUNAT: ' . $result['message']
            );
        }
    }

    /**
     * Obtener ID del propietario de la tienda
     *
     * @since 1.0.0
     * @return int|null
     */
    private function get_store_owner_id() {
        // Buscar el primer usuario con empresa configurada
        // (en un escenario multi-vendor, esto debería ser más sofisticado)
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_companies';

        $company = $wpdb->get_row(
            "SELECT user_id FROM $table WHERE active = 1 ORDER BY id ASC LIMIT 1"
        );

        return $company ? $company->user_id : null;
    }

    /**
     * Log de errores de WooCommerce
     *
     * @since 1.0.0
     * @param int $order_id
     * @param string $message
     */
    private function log_woocommerce_error($order_id, $message) {
        $owner_id = $this->get_store_owner_id();

        if ($owner_id) {
            Sunat_Facturacion_Database::add_log([
                'user_id' => $owner_id,
                'action' => 'woocommerce_error',
                'level' => 'error',
                'message' => sprintf('Pedido #%d: %s', $order_id, $message),
                'created_at' => current_time('mysql')
            ]);
        }

        // También usar logger de WooCommerce
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->error($message, ['source' => 'sunat-facturacion', 'order_id' => $order_id]);
        }
    }

    /**
     * Agregar metabox en pedido con info de comprobante
     *
     * @since 1.0.0
     */
    public function add_order_metabox() {
        add_meta_box(
            'sunat_invoice_info',
            'Comprobante SUNAT',
            [$this, 'render_order_metabox'],
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Renderizar metabox de comprobante
     *
     * @since 1.0.0
     * @param WP_Post $post
     */
    public function render_order_metabox($post) {
        $invoice_id = get_post_meta($post->ID, '_sunat_invoice_id', true);

        if ($invoice_id) {
            $serie = get_post_meta($post->ID, '_sunat_serie', true);
            $numero = get_post_meta($post->ID, '_sunat_numero', true);
            $estado = get_post_meta($post->ID, '_sunat_estado', true);

            $invoice = Sunat_Facturacion_Database::get_invoice($invoice_id);

            echo '<div class="sunat-invoice-info">';
            echo '<p><strong>Serie:</strong> ' . esc_html($serie) . '</p>';
            echo '<p><strong>Número:</strong> ' . esc_html($numero) . '</p>';
            echo '<p><strong>Estado SUNAT:</strong> <span class="status-' . esc_attr(strtolower($estado)) . '">' . esc_html($estado) . '</span></p>';

            if ($invoice) {
                echo '<p><strong>Fecha Emisión:</strong> ' . esc_html($invoice->fecha_emision) . '</p>';

                if ($invoice->mensaje_sunat) {
                    echo '<p><strong>Mensaje SUNAT:</strong> ' . esc_html($invoice->mensaje_sunat) . '</p>';
                }

                // Botón para descargar PDF
                $pdf_url = admin_url('admin-ajax.php?action=sunat_download_invoice_pdf&invoice_id=' . $invoice_id . '&nonce=' . wp_create_nonce('sunat_download_pdf'));
                echo '<p><a href="' . esc_url($pdf_url) . '" class="button button-primary" target="_blank">Descargar PDF</a></p>';

                // Botón para reenviar a SUNAT si está pendiente
                if ($estado === 'PENDIENTE') {
                    echo '<p><button type="button" class="button" onclick="sunatResendToSunat(' . $invoice_id . ', ' . $post->ID . ')">Reenviar a SUNAT</button></p>';
                }
            }

            echo '</div>';

            // JavaScript para reenvío
            ?>
            <script>
            function sunatResendToSunat(invoiceId, orderId) {
                if (!confirm('¿Reenviar este comprobante a SUNAT?')) {
                    return;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sunat_resend_invoice',
                        invoice_id: invoiceId,
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('sunat_resend'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Comprobante reenviado exitosamente');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            }
            </script>
            <?php

        } else {
            echo '<p>No se ha emitido comprobante para este pedido.</p>';

            // Botón para emitir manualmente
            echo '<p><button type="button" class="button button-primary" onclick="sunatEmitInvoice(' . $post->ID . ')">Emitir Comprobante</button></p>';

            // JavaScript para emisión manual
            ?>
            <script>
            function sunatEmitInvoice(orderId) {
                if (!confirm('¿Emitir comprobante para este pedido?')) {
                    return;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sunat_emit_invoice_manual',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('sunat_emit_manual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Comprobante emitido exitosamente');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            }
            </script>
            <?php
        }
    }

    /**
     * AJAX: Emitir comprobante manualmente
     *
     * @since 1.0.0
     */
    public function ajax_emit_invoice_manual() {
        check_ajax_referer('sunat_emit_manual', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $order_id = intval($_POST['order_id']);
        $this->emit_invoice_on_complete($order_id);

        $invoice_id = get_post_meta($order_id, '_sunat_invoice_id', true);

        if ($invoice_id) {
            wp_send_json_success(['message' => 'Comprobante emitido']);
        } else {
            wp_send_json_error(['message' => 'No se pudo emitir el comprobante']);
        }
    }

    /**
     * AJAX: Reenviar comprobante a SUNAT
     *
     * @since 1.0.0
     */
    public function ajax_resend_invoice() {
        check_ajax_referer('sunat_resend', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $invoice_id = intval($_POST['invoice_id']);
        $order_id = intval($_POST['order_id']);

        $result = $this->invoice_generator->resend_to_sunat($invoice_id);

        if ($result['success']) {
            update_post_meta($order_id, '_sunat_estado', 'ACEPTADO');
            wp_send_json_success(['message' => 'Reenviado exitosamente']);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}
