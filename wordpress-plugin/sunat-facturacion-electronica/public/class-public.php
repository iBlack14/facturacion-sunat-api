<?php
/**
 * Panel público (Frontend)
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Public {

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
     * Generador de facturas
     *
     * @since 1.0.0
     * @var Sunat_Facturacion_Invoice_Generator
     */
    private $invoice_generator;

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
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->invoice_generator = new Sunat_Facturacion_Invoice_Generator();
        $this->certificate_manager = new Sunat_Facturacion_Certificate_Manager();
    }

    /**
     * Registrar shortcode
     *
     * @since 1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('sunat_panel', [$this, 'render_panel_shortcode']);
    }

    /**
     * Renderizar panel principal
     *
     * @since 1.0.0
     */
    public function render_panel_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<p>Debes iniciar sesión para acceder al panel de facturación.</p>';
        }

        $atts = shortcode_atts([
            'view' => 'dashboard'
        ], $atts);

        ob_start();

        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : $atts['view'];

        // Renderizar vista según parámetro
        switch ($view) {
            case 'company':
                $this->render_company_view();
                break;

            case 'certificate':
                $this->render_certificate_view();
                break;

            case 'clients':
                $this->render_clients_view();
                break;

            case 'invoices':
                $this->render_invoices_view();
                break;

            case 'new-invoice':
                $this->render_new_invoice_view();
                break;

            case 'dashboard':
            default:
                $this->render_dashboard_view();
                break;
        }

        return ob_get_clean();
    }

    /**
     * Renderizar vista de dashboard
     *
     * @since 1.0.0
     */
    private function render_dashboard_view() {
        $user_id = get_current_user_id();

        // Obtener datos del usuario
        $company = Sunat_Facturacion_Database::get_user_company($user_id);
        $certificate = $this->certificate_manager->get_active_certificate($user_id);
        $stats = Sunat_Facturacion_Database::get_user_stats($user_id);

        // Certificado próximo a vencer
        $certificate_warning = false;
        if ($certificate) {
            $days_remaining = $this->certificate_manager->get_certificate_days_remaining($user_id);
            if ($days_remaining !== null && $days_remaining < 30) {
                $certificate_warning = true;
            }
        }

        include plugin_dir_path(__FILE__) . 'partials/dashboard.php';
    }

    /**
     * Renderizar vista de empresa
     *
     * @since 1.0.0
     */
    private function render_company_view() {
        $user_id = get_current_user_id();

        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sunat_save_company'])) {
            check_admin_referer('sunat_company');

            $data = [
                'ruc' => sanitize_text_field($_POST['ruc']),
                'razon_social' => sanitize_text_field($_POST['razon_social']),
                'nombre_comercial' => sanitize_text_field($_POST['nombre_comercial']),
                'direccion' => sanitize_text_field($_POST['direccion']),
                'departamento' => sanitize_text_field($_POST['departamento']),
                'provincia' => sanitize_text_field($_POST['provincia']),
                'distrito' => sanitize_text_field($_POST['distrito']),
                'ubigeo' => sanitize_text_field($_POST['ubigeo']),
                'usuario_sol' => sanitize_text_field($_POST['usuario_sol']),
                'clave_sol' => sanitize_text_field($_POST['clave_sol']),
                'modo' => sanitize_text_field($_POST['modo']),
                'serie_factura' => sanitize_text_field($_POST['serie_factura']),
                'serie_boleta' => sanitize_text_field($_POST['serie_boleta'])
            ];

            Sunat_Facturacion_Database::save_company($user_id, $data);
            $success_message = 'Datos de empresa guardados correctamente';
        }

        $company = Sunat_Facturacion_Database::get_user_company($user_id);

        include plugin_dir_path(__FILE__) . 'partials/company.php';
    }

    /**
     * Renderizar vista de certificado
     *
     * @since 1.0.0
     */
    private function render_certificate_view() {
        $user_id = get_current_user_id();

        // Procesar subida de certificado
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sunat_upload_certificate'])) {
            check_admin_referer('sunat_certificate');

            if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
                $password = sanitize_text_field($_POST['certificate_password']);
                $result = $this->certificate_manager->upload_certificate($_FILES['certificate'], $password, $user_id);

                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            } else {
                $error_message = 'Error al subir el archivo';
            }
        }

        // Procesar eliminación de certificado
        if (isset($_GET['delete_cert']) && check_admin_referer('sunat_delete_cert_' . $_GET['delete_cert'])) {
            $result = $this->certificate_manager->delete_certificate(intval($_GET['delete_cert']), $user_id);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }

        $certificate = $this->certificate_manager->get_active_certificate($user_id);
        $days_remaining = $certificate ? $this->certificate_manager->get_certificate_days_remaining($user_id) : null;

        include plugin_dir_path(__FILE__) . 'partials/certificate.php';
    }

    /**
     * Renderizar vista de clientes
     *
     * @since 1.0.0
     */
    private function render_clients_view() {
        $user_id = get_current_user_id();

        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sunat_save_client'])) {
            check_admin_referer('sunat_client');

            $data = [
                'user_id' => $user_id,
                'tipo_documento' => sanitize_text_field($_POST['tipo_documento']),
                'numero_documento' => sanitize_text_field($_POST['numero_documento']),
                'razon_social' => sanitize_text_field($_POST['razon_social']),
                'direccion' => sanitize_text_field($_POST['direccion']),
                'email' => sanitize_email($_POST['email']),
                'telefono' => sanitize_text_field($_POST['telefono']),
                'active' => 1
            ];

            if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                $data['id'] = intval($_POST['client_id']);
            }

            Sunat_Facturacion_Database::save_client($data);
            $success_message = 'Cliente guardado correctamente';
        }

        $clients = Sunat_Facturacion_Database::get_user_clients($user_id);
        $editing_client = null;

        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editing_client = Sunat_Facturacion_Database::get_client(intval($_GET['edit']));
        }

        include plugin_dir_path(__FILE__) . 'partials/clients.php';
    }

    /**
     * Renderizar vista de facturas
     *
     * @since 1.0.0
     */
    private function render_invoices_view() {
        $user_id = get_current_user_id();

        // Filtros
        $args = [];
        if (isset($_GET['estado'])) {
            $args['estado_sunat'] = sanitize_text_field($_GET['estado']);
        }

        $invoices = Sunat_Facturacion_Database::get_user_invoices($user_id, $args);

        include plugin_dir_path(__FILE__) . 'partials/invoices.php';
    }

    /**
     * Renderizar vista de nueva factura
     *
     * @since 1.0.0
     */
    private function render_new_invoice_view() {
        $user_id = get_current_user_id();

        // Verificar que tenga empresa y certificado
        $company = Sunat_Facturacion_Database::get_user_company($user_id);
        $certificate = $this->certificate_manager->get_active_certificate($user_id);

        if (!$company || !$certificate) {
            echo '<div class="sunat-alert sunat-alert-warning">';
            echo '<p>Antes de emitir comprobantes debes:</p>';
            echo '<ul>';
            if (!$company) echo '<li>Configurar los <a href="?view=company">datos de tu empresa</a></li>';
            if (!$certificate) echo '<li>Subir tu <a href="?view=certificate">certificado digital</a></li>';
            echo '</ul>';
            echo '</div>';
            return;
        }

        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sunat_emit_invoice'])) {
            check_admin_referer('sunat_emit_invoice');

            // Construir datos del cliente
            $client_data = [
                'tipo_documento' => sanitize_text_field($_POST['client_tipo_documento']),
                'numero_documento' => sanitize_text_field($_POST['client_numero_documento']),
                'razon_social' => sanitize_text_field($_POST['client_razon_social']),
                'direccion' => sanitize_text_field($_POST['client_direccion']),
                'email' => sanitize_email($_POST['client_email'])
            ];

            // Construir items
            $items = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $items[] = [
                        'codigo' => sanitize_text_field($item['codigo']),
                        'descripcion' => sanitize_text_field($item['descripcion']),
                        'unidad' => sanitize_text_field($item['unidad']),
                        'cantidad' => floatval($item['cantidad']),
                        'mto_valor_unitario' => floatval($item['mto_valor_unitario']),
                        'tip_afe_igv' => sanitize_text_field($item['tip_afe_igv'])
                    ];
                }
            }

            $invoice_data = [
                'client' => $client_data,
                'items' => $items,
                'moneda' => 'PEN',
                'observaciones' => sanitize_textarea_field($_POST['observaciones'])
            ];

            $result = $this->invoice_generator->generate_and_emit($invoice_data, $user_id);

            if ($result['success']) {
                $success_message = $result['message'];
                $invoice_id = $result['invoice_id'];
            } else {
                $error_message = $result['message'];
            }
        }

        $clients = Sunat_Facturacion_Database::get_user_clients($user_id);

        include plugin_dir_path(__FILE__) . 'partials/new-invoice.php';
    }

    /**
     * AJAX: Descargar PDF
     *
     * @since 1.0.0
     */
    public function ajax_download_pdf() {
        check_ajax_referer('sunat_download_pdf', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $invoice_id = intval($_GET['invoice_id']);
        $result = $this->invoice_generator->download_pdf($invoice_id);

        if ($result['success']) {
            // Redirigir al PDF o devolver URL
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="comprobante.pdf"');
            echo $result['pdf_content'];
            exit;
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Registrar estilos del frontend
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/public.css',
            [],
            $this->version
        );
    }

    /**
     * Registrar scripts del frontend
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/public.js',
            ['jquery'],
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'sunatPublic', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sunat_public_nonce')
        ]);
    }
}
