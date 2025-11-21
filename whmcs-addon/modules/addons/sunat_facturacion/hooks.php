<?php
/**
 * Hooks de WHMCS para emisión automática de comprobantes SUNAT
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/SunatApiClient.php';
require_once __DIR__ . '/lib/InvoiceGenerator.php';

use WHMCS\Module\Addon\SunatFacturacion\SunatApiClient;
use WHMCS\Module\Addon\SunatFacturacion\InvoiceGenerator;

/**
 * Hook: Cuando invoice cambia de estado
 * Detecta cuando pasa de Draft a Unpaid o a Paid
 */
add_hook('InvoiceChangeStatus', 1, function($vars) {
    try {
        $invoiceId = $vars['invoiceid'];
        $newStatus = $vars['status'];
        $oldStatus = $vars['oldstatus'] ?? null;

        // Log del evento
        logModuleCall('sunat_facturacion', 'InvoiceChangeStatus', [
            'invoice_id' => $invoiceId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ], '');

        // Solo procesar si cambia a Unpaid o Paid
        if (!in_array($newStatus, ['Unpaid', 'Paid'])) {
            return;
        }

        // Verificar si ya existe comprobante para esta invoice
        $existingSunat = Capsule::table('mod_sunat_invoices')
            ->where('whmcs_invoice_id', $invoiceId)
            ->first();

        if ($existingSunat) {
            // Ya existe, no crear duplicado
            logActivity("SUNAT: Invoice #{$invoiceId} ya tiene comprobante asociado");
            return;
        }

        // Obtener configuración de la empresa SUNAT del cliente
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        if (!$invoice) {
            return;
        }

        $client = Capsule::table('tblclients')->where('id', $invoice->userid)->first();
        if (!$client) {
            return;
        }

        // Buscar empresa SUNAT configurada para este cliente
        // Por ahora usar la primera empresa activa
        // TODO: Agregar campo en tblclients para asignar empresa SUNAT específica
        $companyConfig = Capsule::table('mod_sunat_companies')
            ->where('active', 1)
            ->first();

        if (!$companyConfig) {
            logActivity("SUNAT: No hay empresas SUNAT configuradas");
            return;
        }

        // Verificar si debe emitir automáticamente
        if (!$companyConfig->auto_emit) {
            logActivity("SUNAT: Emisión automática desactivada para empresa {$companyConfig->company_name}");
            return;
        }

        // Si cambia a Unpaid, verificar si debe emitir
        if ($newStatus === 'Unpaid' && !$companyConfig->emit_on_unpaid) {
            logActivity("SUNAT: Emisión en Unpaid desactivada, esperando pago");
            return;
        }

        // Emitir comprobante
        emitirComprobanteAutomatico($invoiceId, $companyConfig);

    } catch (\Exception $e) {
        logActivity("SUNAT Error: " . $e->getMessage());

        // Guardar error en logs
        Capsule::table('mod_sunat_logs')->insert([
            'whmcs_invoice_id' => $invoiceId ?? null,
            'action' => 'auto_emit',
            'level' => 'error',
            'message' => $e->getMessage(),
            'data' => json_encode($vars),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
});

/**
 * Hook: Cuando invoice es pagada
 */
add_hook('InvoicePaid', 1, function($vars) {
    try {
        $invoiceId = $vars['invoiceid'];

        // Verificar si ya existe comprobante
        $existingSunat = Capsule::table('mod_sunat_invoices')
            ->where('whmcs_invoice_id', $invoiceId)
            ->first();

        if ($existingSunat) {
            // Ya existe, no crear duplicado
            return;
        }

        // Obtener configuración de empresa
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        if (!$invoice) {
            return;
        }

        $client = Capsule::table('tblclients')->where('id', $invoice->userid)->first();
        if (!$client) {
            return;
        }

        $companyConfig = Capsule::table('mod_sunat_companies')
            ->where('active', 1)
            ->first();

        if (!$companyConfig || !$companyConfig->auto_emit) {
            return;
        }

        // Emitir comprobante
        emitirComprobanteAutomatico($invoiceId, $companyConfig);

    } catch (\Exception $e) {
        logActivity("SUNAT Error en InvoicePaid: " . $e->getMessage());
    }
});

/**
 * Función auxiliar para emitir comprobante
 */
function emitirComprobanteAutomatico($invoiceId, $companyConfig)
{
    try {
        // Crear cliente API
        $apiClient = new SunatApiClient(
            $companyConfig->api_url,
            $companyConfig->api_email,
            decrypt($companyConfig->api_password)
        );

        // Login para obtener token fresco
        $loginResult = $apiClient->login();
        if (!$loginResult['success']) {
            throw new \Exception("Error al hacer login en API: " . ($loginResult['message'] ?? 'Error desconocido'));
        }

        // Actualizar token en BD
        Capsule::table('mod_sunat_companies')
            ->where('id', $companyConfig->id)
            ->update([
                'api_token' => $loginResult['token'],
                'token_expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ]);

        // Configurar generador
        $config = [
            'id' => $companyConfig->id,
            'company_id' => $companyConfig->company_id,
            'branch_id' => $companyConfig->branch_id,
            'serie_factura' => $companyConfig->serie_factura,
            'serie_boleta' => $companyConfig->serie_boleta
        ];

        $generator = new InvoiceGenerator($apiClient, $config);

        // Generar comprobante
        $result = $generator->generateFromWhmcsInvoice($invoiceId);

        if ($result['success']) {
            logActivity("SUNAT: Comprobante {$result['numero_completo']} emitido para Invoice #{$invoiceId} - Estado: {$result['estado_sunat']}");

            // Log exitoso
            Capsule::table('mod_sunat_logs')->insert([
                'whmcs_invoice_id' => $invoiceId,
                'action' => 'auto_emit_success',
                'level' => 'info',
                'message' => "Comprobante {$result['numero_completo']} emitido exitosamente",
                'data' => json_encode($result),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

    } catch (\Exception $e) {
        logActivity("SUNAT Error al emitir comprobante para Invoice #{$invoiceId}: " . $e->getMessage());

        // Log de error
        Capsule::table('mod_sunat_logs')->insert([
            'whmcs_invoice_id' => $invoiceId,
            'action' => 'auto_emit_error',
            'level' => 'error',
            'message' => $e->getMessage(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Hook: Agregar botón en vista de invoice admin
 */
add_hook('AdminInvoicesControlsOutput', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];

    // Verificar si existe comprobante SUNAT
    $sunatInvoice = Capsule::table('mod_sunat_invoices')
        ->where('whmcs_invoice_id', $invoiceId)
        ->first();

    if ($sunatInvoice) {
        // Mostrar info del comprobante
        $html = '<div class="alert alert-info" style="margin-top: 10px;">';
        $html .= '<strong>Comprobante SUNAT:</strong> ' . $sunatInvoice->numero_completo;
        $html .= ' | <strong>Estado:</strong> ' . $sunatInvoice->estado_sunat;

        if ($sunatInvoice->pdf_path) {
            $html .= ' | <a href="addonmodules.php?module=sunat_facturacion&action=download&type=pdf&id=' . $sunatInvoice->id . '" target="_blank">Descargar PDF</a>';
        }

        $html .= '</div>';
        return $html;
    }

    return '';
});
