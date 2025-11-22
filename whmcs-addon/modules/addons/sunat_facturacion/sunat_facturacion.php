<?php
/**
 * WHMCS Addon - Facturación Electrónica SUNAT Perú
 *
 * Integración con API de Facturación Electrónica SUNAT
 * Emisión automática de Facturas y Boletas desde WHMCS
 *
 * @author BLXKSTUDIO
 * @version 1.0.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Configuración del addon
 */
function sunat_facturacion_config()
{
    return [
        'name' => 'Facturación SUNAT Perú',
        'description' => 'Emisión automática de Facturas y Boletas electrónicas SUNAT desde WHMCS. Soporte multi-empresa.',
        'version' => '1.0.0',
        'author' => 'BLXKSTUDIO',
        'language' => 'spanish',
        'fields' => []
    ];
}

/**
 * Activación del addon
 * Crea las tablas necesarias en la base de datos
 */
function sunat_facturacion_activate()
{
    try {
        // Tabla de empresas SUNAT configuradas
        if (!Capsule::schema()->hasTable('mod_sunat_companies')) {
            Capsule::schema()->create('mod_sunat_companies', function ($table) {
                $table->increments('id');
                $table->string('company_name', 100)->comment('Nombre identificador');
                $table->string('api_url', 255)->comment('URL de la API SUNAT');
                $table->string('api_email', 100)->comment('Email para login API');
                $table->string('api_password', 255)->comment('Password para login API (encriptado)');
                $table->text('api_token')->nullable()->comment('Token de acceso API');
                $table->timestamp('token_expires_at')->nullable()->comment('Expiración del token');
                $table->integer('company_id')->comment('ID empresa en API SUNAT');
                $table->integer('branch_id')->comment('ID sucursal en API SUNAT');
                $table->enum('modo', ['beta', 'produccion'])->default('beta')->comment('Ambiente SUNAT');
                $table->string('serie_factura', 10)->default('F001')->comment('Serie para facturas');
                $table->string('serie_boleta', 10)->default('B001')->comment('Serie para boletas');
                $table->boolean('auto_emit')->default(1)->comment('Emisión automática');
                $table->boolean('emit_on_unpaid')->default(1)->comment('Emitir cuando pasa a Unpaid');
                $table->boolean('active')->default(1)->comment('Empresa activa');
                $table->timestamps();
            });
        }

        // Tabla de invoices/boletas emitidas
        if (!Capsule::schema()->hasTable('mod_sunat_invoices')) {
            Capsule::schema()->create('mod_sunat_invoices', function ($table) {
                $table->increments('id');
                $table->integer('whmcs_invoice_id')->comment('ID invoice WHMCS');
                $table->integer('sunat_company_id')->comment('ID empresa SUNAT configurada');
                $table->integer('sunat_invoice_id')->nullable()->comment('ID en API SUNAT');
                $table->string('tipo_documento', 10)->comment('01=Factura, 03=Boleta');
                $table->string('serie', 10)->comment('F001, B001, etc');
                $table->string('correlativo', 20)->nullable()->comment('000001, etc');
                $table->string('numero_completo', 30)->nullable()->comment('F001-000001');
                $table->enum('estado_sunat', ['PENDIENTE', 'ACEPTADO', 'RECHAZADO', 'ERROR'])->default('PENDIENTE');
                $table->string('codigo_hash', 100)->nullable()->comment('Hash del comprobante');
                $table->string('xml_path', 255)->nullable();
                $table->string('cdr_path', 255)->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->text('respuesta_sunat')->nullable()->comment('Respuesta JSON de SUNAT');
                $table->text('error_message')->nullable()->comment('Mensaje de error si falla');
                $table->timestamps();

                $table->index('whmcs_invoice_id');
                $table->index('estado_sunat');
            });
        }

        // Tabla de mapeo de productos WHMCS → SUNAT
        if (!Capsule::schema()->hasTable('mod_sunat_product_mapping')) {
            Capsule::schema()->create('mod_sunat_product_mapping', function ($table) {
                $table->increments('id');
                $table->integer('whmcs_product_id')->comment('ID producto WHMCS');
                $table->string('sunat_code', 20)->comment('Código producto SUNAT');
                $table->string('sunat_description', 255)->comment('Descripción para SUNAT');
                $table->string('sunat_unit', 10)->default('ZZ')->comment('Unidad de medida');
                $table->string('sunat_tip_afe_igv', 2)->default('10')->comment('Tipo afectación IGV');
                $table->timestamps();

                $table->unique('whmcs_product_id');
            });
        }

        // Tabla de logs
        if (!Capsule::schema()->hasTable('mod_sunat_logs')) {
            Capsule::schema()->create('mod_sunat_logs', function ($table) {
                $table->increments('id');
                $table->integer('whmcs_invoice_id')->nullable();
                $table->string('action', 50)->comment('create, send, error, etc');
                $table->enum('level', ['info', 'warning', 'error'])->default('info');
                $table->text('message');
                $table->text('data')->nullable()->comment('JSON con datos adicionales');
                $table->timestamp('created_at')->useCurrent();

                $table->index('whmcs_invoice_id');
                $table->index('level');
            });
        }

        return [
            'status' => 'success',
            'description' => 'Módulo activado correctamente. Tablas creadas en la base de datos.'
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error al activar el módulo: ' . $e->getMessage()
        ];
    }
}

/**
 * Desactivación del addon
 * NO elimina las tablas para conservar los datos
 */
function sunat_facturacion_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desactivado. Los datos se han conservado.'
    ];
}

/**
 * Upgrade del addon
 */
function sunat_facturacion_upgrade($vars)
{
    $currentVersion = $vars['version'];

    // Aquí irán futuras migraciones de base de datos

    return [
        'status' => 'success',
        'description' => 'Módulo actualizado correctamente.'
    ];
}

/**
 * Output del addon en el panel de administración
 */
function sunat_facturacion_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];

    // Obtener la acción solicitada
    $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

    echo '<link rel="stylesheet" href="modules/addons/sunat_facturacion/templates/admin/style.css">';

    echo '<div class="sunat-addon-container">';
    echo '<h2>Facturación Electrónica SUNAT Perú <small>v' . $version . '</small></h2>';

    // Menú de navegación
    echo '<ul class="nav nav-tabs" role="tablist">';
    echo '<li class="' . ($action == 'dashboard' ? 'active' : '') . '"><a href="' . $modulelink . '&action=dashboard">Dashboard</a></li>';
    echo '<li class="' . ($action == 'companies' ? 'active' : '') . '"><a href="' . $modulelink . '&action=companies">Empresas</a></li>';
    echo '<li class="' . ($action == 'invoices' ? 'active' : '') . '"><a href="' . $modulelink . '&action=invoices">Comprobantes</a></li>';
    echo '<li class="' . ($action == 'mapping' ? 'active' : '') . '"><a href="' . $modulelink . '&action=mapping">Mapeo Productos</a></li>';
    echo '<li class="' . ($action == 'logs' ? 'active' : '') . '"><a href="' . $modulelink . '&action=logs">Logs</a></li>';
    echo '</ul>';

    echo '<div class="tab-content">';

    // Cargar la vista correspondiente
    // Usar include (no require_once) para mantener las variables en scope
    switch ($action) {
        case 'companies':
            include __DIR__ . '/templates/admin/companies.php';
            break;
        case 'invoices':
            include __DIR__ . '/templates/admin/invoices.php';
            break;
        case 'mapping':
            include __DIR__ . '/templates/admin/mapping.php';
            break;
        case 'logs':
            include __DIR__ . '/templates/admin/logs.php';
            break;
        case 'dashboard':
        default:
            include __DIR__ . '/templates/admin/dashboard.php';
            break;
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Sidebar del addon
 */
function sunat_facturacion_sidebar($vars)
{
    $modulelink = $vars['modulelink'];

    $sidebar = '<div class="panel panel-default">';
    $sidebar .= '<div class="panel-heading"><strong>Acceso Rápido</strong></div>';
    $sidebar .= '<div class="list-group">';
    $sidebar .= '<a href="' . $modulelink . '&action=companies" class="list-group-item">Gestionar Empresas</a>';
    $sidebar .= '<a href="' . $modulelink . '&action=invoices&filter=pendiente" class="list-group-item">Comprobantes Pendientes</a>';
    $sidebar .= '<a href="' . $modulelink . '&action=mapping" class="list-group-item">Configurar Productos</a>';
    $sidebar .= '</div>';
    $sidebar .= '</div>';

    $sidebar .= '<div class="panel panel-info">';
    $sidebar .= '<div class="panel-heading"><strong>Información</strong></div>';
    $sidebar .= '<div class="panel-body">';
    $sidebar .= '<p><strong>Versión:</strong> ' . $vars['version'] . '</p>';
    $sidebar .= '<p><strong>Autor:</strong> BLXKSTUDIO</p>';
    $sidebar .= '<p><a href="https://docs.sunat.gob.pe" target="_blank">Documentación SUNAT</a></p>';
    $sidebar .= '</div>';
    $sidebar .= '</div>';

    return $sidebar;
}
