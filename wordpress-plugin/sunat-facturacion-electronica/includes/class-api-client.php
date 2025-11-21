<?php
/**
 * Cliente API para comunicación con API SUNAT
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_API_Client {

    /**
     * URL base de la API
     *
     * @var string
     */
    private $api_url;

    /**
     * Token de autenticación
     *
     * @var string
     */
    private $token;

    /**
     * Email para login
     *
     * @var string
     */
    private $email;

    /**
     * Password para login
     *
     * @var string
     */
    private $password;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct($api_url = null, $email = null, $password = null, $token = null) {
        $this->api_url = $api_url ?: get_option('sunat_facturacion_api_url');
        $this->email = $email;
        $this->password = $password;
        $this->token = $token;
    }

    /**
     * Login y obtener token
     *
     * @since 1.0.0
     * @return array
     */
    public function login() {
        $response = $this->request('POST', '/api/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
            'token_name' => 'WordPress Plugin',
            'abilities' => ['*']
        ], false);

        if (isset($response['access_token'])) {
            $this->token = $response['access_token'];
            return [
                'success' => true,
                'token' => $this->token,
                'user' => $response['user'] ?? null
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Error al hacer login'
        ];
    }

    /**
     * Crear factura
     *
     * @since 1.0.0
     * @param array $data
     * @return array
     */
    public function create_invoice($data) {
        return $this->request('POST', '/api/v1/invoices', $data);
    }

    /**
     * Crear boleta
     *
     * @since 1.0.0
     * @param array $data
     * @return array
     */
    public function create_boleta($data) {
        return $this->request('POST', '/api/v1/boletas', $data);
    }

    /**
     * Enviar comprobante a SUNAT
     *
     * @since 1.0.0
     * @param string $type factura|boleta
     * @param int $id
     * @return array
     */
    public function send_to_sunat($type, $id) {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('POST', "{$endpoint}/{$id}/send-sunat");
    }

    /**
     * Obtener comprobante
     *
     * @since 1.0.0
     * @param string $type
     * @param int $id
     * @return array
     */
    public function get_invoice($type, $id) {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('GET', "{$endpoint}/{$id}");
    }

    /**
     * Descargar PDF
     *
     * @since 1.0.0
     * @param string $type
     * @param int $id
     * @return array
     */
    public function download_pdf($type, $id) {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->download_file("{$endpoint}/{$id}/download-pdf");
    }

    /**
     * Generar PDF
     *
     * @since 1.0.0
     * @param string $type
     * @param int $id
     * @return array
     */
    public function generate_pdf($type, $id) {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('POST', "{$endpoint}/{$id}/generate-pdf");
    }

    /**
     * Test de conexión
     *
     * @since 1.0.0
     * @return array
     */
    public function test_connection() {
        try {
            $response = $this->request('GET', '/api/health', [], false);
            return [
                'success' => isset($response['status']) && $response['status'] === 'ok',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Realizar petición HTTP
     *
     * @since 1.0.0
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param bool $requires_auth
     * @return array
     */
    private function request($method, $endpoint, $data = [], $requires_auth = true) {
        $url = rtrim($this->api_url, '/') . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 60
        ];

        if ($requires_auth && $this->token) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('Error de conexión: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        $result = json_decode($body, true);

        if ($status_code >= 400) {
            throw new Exception($result['message'] ?? "Error HTTP {$status_code}");
        }

        return $result;
    }

    /**
     * Descargar archivo
     *
     * @since 1.0.0
     * @param string $endpoint
     * @return array
     */
    private function download_file($endpoint) {
        $url = rtrim($this->api_url, '/') . $endpoint;

        $args = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/octet-stream'
            ]
        ];

        if ($this->token) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('Error de descarga: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 400) {
            throw new Exception("Error HTTP {$status_code}");
        }

        return [
            'success' => true,
            'content' => $body,
            'content_type' => wp_remote_retrieve_header($response, 'content-type')
        ];
    }

    /**
     * Establecer token
     *
     * @since 1.0.0
     * @param string $token
     */
    public function set_token($token) {
        $this->token = $token;
    }

    /**
     * Obtener token
     *
     * @since 1.0.0
     * @return string
     */
    public function get_token() {
        return $this->token;
    }
}
