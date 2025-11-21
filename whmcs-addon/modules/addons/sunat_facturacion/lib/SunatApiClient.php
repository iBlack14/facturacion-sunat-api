<?php
/**
 * Cliente API para comunicación con API de Facturación SUNAT
 */

namespace WHMCS\Module\Addon\SunatFacturacion;

class SunatApiClient
{
    private $apiUrl;
    private $token;
    private $email;
    private $password;

    /**
     * Constructor
     */
    public function __construct($apiUrl, $email = null, $password = null, $token = null)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->email = $email;
        $this->password = $password;
        $this->token = $token;
    }

    /**
     * Login y obtener token
     */
    public function login()
    {
        $response = $this->request('POST', '/api/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
            'token_name' => 'WHMCS Addon',
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
     */
    public function createInvoice($data)
    {
        return $this->request('POST', '/api/v1/invoices', $data);
    }

    /**
     * Crear boleta
     */
    public function createBoleta($data)
    {
        return $this->request('POST', '/api/v1/boletas', $data);
    }

    /**
     * Enviar comprobante a SUNAT
     */
    public function sendToSunat($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('POST', "{$endpoint}/{$id}/send-sunat");
    }

    /**
     * Obtener comprobante
     */
    public function getInvoice($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('GET', "{$endpoint}/{$id}");
    }

    /**
     * Descargar PDF
     */
    public function downloadPdf($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->downloadFile("{$endpoint}/{$id}/download-pdf");
    }

    /**
     * Descargar XML
     */
    public function downloadXml($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->downloadFile("{$endpoint}/{$id}/download-xml");
    }

    /**
     * Descargar CDR
     */
    public function downloadCdr($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->downloadFile("{$endpoint}/{$id}/download-cdr");
    }

    /**
     * Generar PDF
     */
    public function generatePdf($type, $id)
    {
        $endpoint = $type === 'factura' ? '/api/v1/invoices' : '/api/v1/boletas';
        return $this->request('POST', "{$endpoint}/{$id}/generate-pdf");
    }

    /**
     * Test de conexión
     */
    public function testConnection()
    {
        try {
            $response = $this->request('GET', '/api/health', [], false);
            return [
                'success' => isset($response['status']) && $response['status'] === 'ok',
                'data' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Realizar petición HTTP
     */
    private function request($method, $endpoint, $data = [], $requiresAuth = true)
    {
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($requiresAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception("Error de conexión: {$error}");
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \Exception($result['message'] ?? "Error HTTP {$httpCode}");
        }

        return $result;
    }

    /**
     * Descargar archivo
     */
    private function downloadFile($endpoint)
    {
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Accept: application/octet-stream'
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception("Error de descarga: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \Exception("Error HTTP {$httpCode}");
        }

        return [
            'success' => true,
            'content' => $response,
            'content_type' => $contentType
        ];
    }

    /**
     * Establecer token manualmente
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Obtener token actual
     */
    public function getToken()
    {
        return $this->token;
    }
}
