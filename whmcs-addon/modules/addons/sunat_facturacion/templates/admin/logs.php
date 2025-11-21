<?php
use WHMCS\Database\Capsule;

// Obtener logs
$logs = Capsule::table('mod_sunat_logs')
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Logs del Sistema</h3>
    </div>
    <div class="panel-body">
        <?php if (count($logs) > 0): ?>
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th width="150">Fecha</th>
                        <th width="80">Invoice</th>
                        <th width="120">Acción</th>
                        <th width="80">Nivel</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log->created_at)); ?></td>
                            <td>
                                <?php if ($log->whmcs_invoice_id): ?>
                                    <a href="invoices.php?action=edit&id=<?php echo $log->whmcs_invoice_id; ?>" target="_blank">
                                        #<?php echo $log->whmcs_invoice_id; ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo $log->action; ?></small></td>
                            <td>
                                <?php
                                $badgeClass = 'default';
                                if ($log->level == 'error') $badgeClass = 'danger';
                                elseif ($log->level == 'warning') $badgeClass = 'warning';
                                elseif ($log->level == 'info') $badgeClass = 'info';
                                ?>
                                <span class="label label-<?php echo $badgeClass; ?>"><?php echo $log->level; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($log->message); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                No hay logs registrados aún.
            </div>
        <?php endif; ?>
    </div>
</div>
