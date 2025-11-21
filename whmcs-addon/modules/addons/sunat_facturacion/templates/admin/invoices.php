<?php
use WHMCS\Database\Capsule;

// Obtener comprobantes con filtros
$query = Capsule::table('mod_sunat_invoices as si')
    ->join('mod_sunat_companies as sc', 'si.sunat_company_id', '=', 'sc.id')
    ->join('tblinvoices as i', 'si.whmcs_invoice_id', '=', 'i.id')
    ->join('tblclients as c', 'i.userid', '=', 'c.id')
    ->select('si.*', 'sc.company_name', 'i.invoicenum', 'c.firstname', 'c.lastname', 'c.companyname');

// Filtros
if (isset($_GET['filter']) && $_GET['filter'] == 'pendiente') {
    $query->where('si.estado_sunat', 'PENDIENTE');
}
if (isset($_GET['filter']) && $_GET['filter'] == 'rechazado') {
    $query->where('si.estado_sunat', 'RECHAZADO');
}

$comprobantes = $query->orderBy('si.created_at', 'desc')->paginate(20);
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Comprobantes Electrónicos SUNAT</h3>
    </div>
    <div class="panel-body">
        <div class="btn-group" role="group">
            <a href="<?php echo $modulelink; ?>&action=invoices" class="btn btn-sm btn-default">Todos</a>
            <a href="<?php echo $modulelink; ?>&action=invoices&filter=pendiente" class="btn btn-sm btn-warning">Pendientes</a>
            <a href="<?php echo $modulelink; ?>&action=invoices&filter=rechazado" class="btn btn-sm btn-danger">Rechazados</a>
        </div>

        <hr>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invoice</th>
                    <th>Cliente</th>
                    <th>Empresa</th>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comprobantes as $comprobante): ?>
                    <tr>
                        <td><?php echo $comprobante->id; ?></td>
                        <td>
                            <a href="invoices.php?action=edit&id=<?php echo $comprobante->whmcs_invoice_id; ?>" target="_blank">
                                #<?php echo $comprobante->whmcs_invoice_id; ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $clientName = !empty($comprobante->companyname)
                                ? $comprobante->companyname
                                : $comprobante->firstname . ' ' . $comprobante->lastname;
                            echo $clientName;
                            ?>
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
                                <a href="#" class="btn btn-xs btn-primary" title="PDF">
                                    <i class="fa fa-file-pdf-o"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($comprobante->xml_path): ?>
                                <a href="#" class="btn btn-xs btn-info" title="XML">
                                    <i class="fa fa-file-code-o"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
