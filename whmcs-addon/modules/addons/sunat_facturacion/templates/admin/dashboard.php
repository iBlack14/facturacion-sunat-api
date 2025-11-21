<?php
use WHMCS\Database\Capsule;

// Estadísticas
$totalCompanies = Capsule::table('mod_sunat_companies')->where('active', 1)->count();
$totalComprobantes = Capsule::table('mod_sunat_invoices')->count();
$comprobantesAceptados = Capsule::table('mod_sunat_invoices')->where('estado_sunat', 'ACEPTADO')->count();
$comprobantesPendientes = Capsule::table('mod_sunat_invoices')->where('estado_sunat', 'PENDIENTE')->count();
$comprobantesRechazados = Capsule::table('mod_sunat_invoices')->where('estado_sunat', 'RECHAZADO')->count();

// Últimos comprobantes
$ultimosComprobantes = Capsule::table('mod_sunat_invoices as si')
    ->join('mod_sunat_companies as sc', 'si.sunat_company_id', '=', 'sc.id')
    ->join('tblinvoices as i', 'si.whmcs_invoice_id', '=', 'i.id')
    ->select('si.*', 'sc.company_name', 'i.userid')
    ->orderBy('si.created_at', 'desc')
    ->limit(10)
    ->get();
?>

<div class="row">
    <div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-body text-center">
                <h3><?php echo $totalCompanies; ?></h3>
                <p>Empresas Activas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-success">
            <div class="panel-body text-center">
                <h3><?php echo $comprobantesAceptados; ?></h3>
                <p>Comprobantes Aceptados</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-body text-center">
                <h3><?php echo $comprobantesPendientes; ?></h3>
                <p>Pendientes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-danger">
            <div class="panel-body text-center">
                <h3><?php echo $comprobantesRechazados; ?></h3>
                <p>Rechazados</p>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Últimos Comprobantes Emitidos</h3>
    </div>
    <div class="panel-body">
        <?php if (count($ultimosComprobantes) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Invoice WHMCS</th>
                        <th>Empresa</th>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Estado SUNAT</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosComprobantes as $comprobante): ?>
                        <tr>
                            <td>
                                <a href="invoices.php?action=edit&id=<?php echo $comprobante->whmcs_invoice_id; ?>" target="_blank">
                                    #<?php echo $comprobante->whmcs_invoice_id; ?>
                                </a>
                            </td>
                            <td><?php echo $comprobante->company_name; ?></td>
                            <td><strong><?php echo $comprobante->numero_completo; ?></strong></td>
                            <td><?php echo $comprobante->tipo_documento == '01' ? 'Factura' : 'Boleta'; ?></td>
                            <td>
                                <?php
                                $badgeClass = 'default';
                                if ($comprobante->estado_sunat == 'ACEPTADO') $badgeClass = 'success';
                                elseif ($comprobante->estado_sunat == 'PENDIENTE') $badgeClass = 'warning';
                                elseif ($comprobante->estado_sunat == 'RECHAZADO') $badgeClass = 'danger';
                                ?>
                                <span class="label label-<?php echo $badgeClass; ?>">
                                    <?php echo $comprobante->estado_sunat; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comprobante->created_at)); ?></td>
                            <td>
                                <?php if ($comprobante->pdf_path): ?>
                                    <a href="<?php echo $modulelink; ?>&action=download&type=pdf&id=<?php echo $comprobante->id; ?>" class="btn btn-xs btn-primary" title="Descargar PDF">
                                        <i class="fa fa-file-pdf-o"></i> PDF
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                No hay comprobantes emitidos aún.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <strong><i class="fa fa-info-circle"></i> Inicio Rápido:</strong>
    <ol style="margin-top: 10px; margin-bottom: 0;">
        <li>Configura al menos una <a href="<?php echo $modulelink; ?>&action=companies">Empresa SUNAT</a></li>
        <li>Opcionalmente mapea tus <a href="<?php echo $modulelink; ?>&action=mapping">Productos a códigos SUNAT</a></li>
        <li>El sistema emitirá automáticamente cuando las invoices cambien a Unpaid o Paid</li>
    </ol>
</div>
