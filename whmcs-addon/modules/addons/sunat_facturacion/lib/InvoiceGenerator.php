<?php
/**
 * Generador de Facturas/Boletas desde WHMCS
 */

namespace WHMCS\Module\Addon\SunatFacturacion;

use WHMCS\Database\Capsule;

class InvoiceGenerator
{
    private $apiClient;
    private $companyConfig;

    /**
     * Constructor
     */
    public function __construct($apiClient, $companyConfig)
    {
        $this->apiClient = $apiClient;
        $this->companyConfig = $companyConfig;
    }

    /**
     * Generar comprobante desde invoice WHMCS
     */
    public function generateFromWhmcsInvoice($invoiceId)
    {
        // Obtener datos de la invoice WHMCS
        $invoice = $this->getWhmcsInvoice($invoiceId);
        if (!$invoice) {
            throw new \Exception("Invoice #{$invoiceId} no encontrada");
        }

        // Obtener cliente
        $client = $this->getWhmcsClient($invoice->userid);
        if (!$client) {
            throw new \Exception("Cliente no encontrado");
        }

        // Detectar tipo de documento basado en Tax ID
        $documentType = $this->detectDocumentType($client->tax_id);

        // Obtener items de la invoice
        $items = $this->getInvoiceItems($invoiceId);

        // Construir payload para API
        $payload = $this->buildPayload($invoice, $client, $items, $documentType);

        // Crear comprobante en API
        if ($documentType['tipo_comprobante'] === 'factura') {
            $result = $this->apiClient->createInvoice($payload);
        } else {
            $result = $this->apiClient->createBoleta($payload);
        }

        if (!isset($result['data']['id'])) {
            throw new \Exception($result['message'] ?? 'Error al crear comprobante');
        }

        $sunatInvoiceId = $result['data']['id'];

        // Guardar en base de datos local
        $localId = $this->saveSunatInvoice($invoiceId, $sunatInvoiceId, $result['data'], $documentType);

        // Enviar a SUNAT automáticamente
        $sendResult = $this->apiClient->sendToSunat($documentType['tipo_comprobante'], $sunatInvoiceId);

        // Actualizar con respuesta de SUNAT
        $this->updateSunatInvoice($localId, $sendResult);

        return [
            'success' => true,
            'invoice_id' => $sunatInvoiceId,
            'numero_completo' => $sendResult['data']['numero_completo'] ?? null,
            'estado_sunat' => $sendResult['data']['estado_sunat'] ?? 'PENDIENTE',
            'sunat_response' => $sendResult
        ];
    }

    /**
     * Detectar tipo de documento basado en Tax ID
     */
    private function detectDocumentType($taxId)
    {
        // Limpiar Tax ID
        $taxId = preg_replace('/[^0-9]/', '', $taxId);
        $length = strlen($taxId);

        if ($length == 11) {
            // RUC - Factura
            return [
                'tipo_documento' => '6',
                'tipo_comprobante' => 'factura',
                'serie' => $this->companyConfig['serie_factura']
            ];
        } elseif ($length == 8) {
            // DNI - Boleta
            return [
                'tipo_documento' => '1',
                'tipo_comprobante' => 'boleta',
                'serie' => $this->companyConfig['serie_boleta']
            ];
        } else {
            // Por defecto: Boleta con documento genérico
            return [
                'tipo_documento' => '0',
                'tipo_comprobante' => 'boleta',
                'serie' => $this->companyConfig['serie_boleta']
            ];
        }
    }

    /**
     * Construir payload para API SUNAT
     */
    private function buildPayload($invoice, $client, $items, $documentType)
    {
        // Datos del cliente
        $clientData = [
            'tipo_documento' => $documentType['tipo_documento'],
            'numero_documento' => preg_replace('/[^0-9]/', '', $client->tax_id),
            'razon_social' => !empty($client->companyname) ? $client->companyname : $client->firstname . ' ' . $client->lastname,
            'direccion' => $client->address1 . (!empty($client->address2) ? ', ' . $client->address2 : ''),
        ];

        // Agregar email si existe
        if (!empty($client->email)) {
            $clientData['email'] = $client->email;
        }

        // Mapear items
        $detalles = [];
        foreach ($items as $item) {
            $detalles[] = $this->mapInvoiceItem($item);
        }

        // Payload base
        $payload = [
            'company_id' => $this->companyConfig['company_id'],
            'branch_id' => $this->companyConfig['branch_id'],
            'serie' => $documentType['serie'],
            'fecha_emision' => date('Y-m-d'),
            'moneda' => $invoice->currency == 'USD' ? 'USD' : 'PEN',
            'tipo_operacion' => '0101',
            'forma_pago_tipo' => 'Contado', // TODO: Detectar si es crédito
            'client' => $clientData,
            'detalles' => $detalles
        ];

        return $payload;
    }

    /**
     * Mapear item de WHMCS a detalle SUNAT
     */
    private function mapInvoiceItem($item)
    {
        // Buscar mapeo de producto
        $mapping = null;
        if ($item->relid > 0) {
            $mapping = Capsule::table('mod_sunat_product_mapping')
                ->where('whmcs_product_id', $item->relid)
                ->first();
        }

        if ($mapping) {
            // CON MAPEO: Usar configuración guardada
            return [
                'codigo' => $mapping->sunat_code,
                'descripcion' => $mapping->sunat_description,
                'unidad' => $mapping->sunat_unit,
                'cantidad' => 1,
                'mto_valor_unitario' => round($item->amount, 2),
                'porcentaje_igv' => 18,
                'tip_afe_igv' => $mapping->sunat_tip_afe_igv
            ];
        } else {
            // SIN MAPEO: Usar datos del producto WHMCS
            $codigo = $item->relid > 0 ? 'PROD-' . $item->relid : 'ITEM-' . $item->id;

            return [
                'codigo' => $codigo,
                'descripcion' => $item->description,
                'unidad' => 'ZZ', // Servicio genérico
                'cantidad' => 1,
                'mto_valor_unitario' => round($item->amount, 2),
                'porcentaje_igv' => 18,
                'tip_afe_igv' => '10' // Gravado por defecto
            ];
        }
    }

    /**
     * Obtener invoice WHMCS
     */
    private function getWhmcsInvoice($invoiceId)
    {
        return Capsule::table('tblinvoices')
            ->where('id', $invoiceId)
            ->first();
    }

    /**
     * Obtener cliente WHMCS
     */
    private function getWhmcsClient($userId)
    {
        return Capsule::table('tblclients')
            ->where('id', $userId)
            ->first();
    }

    /**
     * Obtener items de la invoice
     */
    private function getInvoiceItems($invoiceId)
    {
        return Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->where('amount', '>', 0)
            ->get();
    }

    /**
     * Guardar comprobante SUNAT en BD local
     */
    private function saveSunatInvoice($whmcsInvoiceId, $sunatInvoiceId, $data, $documentType)
    {
        $id = Capsule::table('mod_sunat_invoices')->insertGetId([
            'whmcs_invoice_id' => $whmcsInvoiceId,
            'sunat_company_id' => $this->companyConfig['id'],
            'sunat_invoice_id' => $sunatInvoiceId,
            'tipo_documento' => $documentType['tipo_comprobante'] === 'factura' ? '01' : '03',
            'serie' => $data['serie'] ?? $documentType['serie'],
            'correlativo' => $data['correlativo'] ?? null,
            'numero_completo' => $data['numero_completo'] ?? null,
            'estado_sunat' => 'PENDIENTE',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $id;
    }

    /**
     * Actualizar comprobante con respuesta de SUNAT
     */
    private function updateSunatInvoice($localId, $sendResult)
    {
        $data = $sendResult['data'] ?? [];

        $updateData = [
            'estado_sunat' => $data['estado_sunat'] ?? 'ERROR',
            'numero_completo' => $data['numero_completo'] ?? null,
            'correlativo' => $data['correlativo'] ?? null,
            'codigo_hash' => $data['codigo_hash'] ?? null,
            'xml_path' => $data['xml_path'] ?? null,
            'cdr_path' => $data['cdr_path'] ?? null,
            'respuesta_sunat' => json_encode($data['respuesta_sunat'] ?? []),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!$sendResult['success']) {
            $updateData['error_message'] = $sendResult['message'] ?? 'Error desconocido';
        }

        Capsule::table('mod_sunat_invoices')
            ->where('id', $localId)
            ->update($updateData);

        // Agregar nota a la invoice WHMCS
        if (isset($data['numero_completo'])) {
            $this->addNoteToWhmcsInvoice(
                Capsule::table('mod_sunat_invoices')->where('id', $localId)->value('whmcs_invoice_id'),
                "Comprobante SUNAT: {$data['numero_completo']} - Estado: {$data['estado_sunat']}"
            );
        }
    }

    /**
     * Agregar nota a invoice WHMCS
     */
    private function addNoteToWhmcsInvoice($invoiceId, $note)
    {
        localAPI('AddInvoicePayment', [
            'invoiceid' => $invoiceId,
            'notes' => $note,
            'noemail' => true
        ]);
    }
}
