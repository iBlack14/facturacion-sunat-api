<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Mis Comprobantes</h2>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard">Dashboard</a>
        <a href="?view=company">Mi Empresa</a>
        <a href="?view=certificate">Certificado</a>
        <a href="?view=clients">Clientes</a>
        <a href="?view=invoices" class="active">Comprobantes</a>
        <a href="?view=new-invoice" class="sunat-btn-primary">Nueva Factura</a>
    </nav>

    <!-- Filtros -->
    <div class="sunat-filters">
        <form method="get" action="">
            <input type="hidden" name="view" value="invoices">

            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="ACEPTADO" <?php selected(isset($_GET['estado']) && $_GET['estado'] === 'ACEPTADO'); ?>>Aceptado</option>
                <option value="PENDIENTE" <?php selected(isset($_GET['estado']) && $_GET['estado'] === 'PENDIENTE'); ?>>Pendiente</option>
                <option value="RECHAZADO" <?php selected(isset($_GET['estado']) && $_GET['estado'] === 'RECHAZADO'); ?>>Rechazado</option>
            </select>

            <button type="submit" class="sunat-btn">Filtrar</button>
            <a href="?view=invoices" class="sunat-btn sunat-btn-secondary">Limpiar</a>
        </form>
    </div>

    <!-- Tabla de comprobantes -->
    <?php if (!empty($invoices)): ?>
        <table class="sunat-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Serie-Número</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($invoice->fecha_emision)); ?></td>
                        <td><strong><?php echo esc_html($invoice->serie . '-' . $invoice->numero); ?></strong></td>
                        <td>
                            <?php
                            $tipos = ['01' => 'Factura', '03' => 'Boleta'];
                            echo $tipos[$invoice->tipo_documento] ?? $invoice->tipo_documento;
                            ?>
                        </td>
                        <td>
                            <?php echo esc_html($invoice->cliente_razon_social); ?><br>
                            <small><?php echo esc_html($invoice->cliente_numero_documento); ?></small>
                        </td>
                        <td>
                            <?php echo $invoice->moneda; ?> <?php echo number_format($invoice->mto_imp_venta, 2); ?>
                        </td>
                        <td>
                            <?php
                            $badge_class = 'default';
                            if ($invoice->estado_sunat === 'ACEPTADO') {
                                $badge_class = 'success';
                            } elseif ($invoice->estado_sunat === 'PENDIENTE') {
                                $badge_class = 'warning';
                            } elseif ($invoice->estado_sunat === 'RECHAZADO') {
                                $badge_class = 'danger';
                            }
                            ?>
                            <span class="sunat-badge sunat-badge-<?php echo $badge_class; ?>">
                                <?php echo esc_html($invoice->estado_sunat); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin-ajax.php?action=sunat_download_pdf&invoice_id=' . $invoice->id . '&nonce=' . wp_create_nonce('sunat_download_pdf')); ?>"
                               class="sunat-btn sunat-btn-small" target="_blank">
                                PDF
                            </a>
                            <?php if ($invoice->estado_sunat === 'PENDIENTE'): ?>
                                <button type="button" class="sunat-btn sunat-btn-small"
                                        onclick="sunatResendInvoice(<?php echo $invoice->id; ?>)">
                                    Reenviar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="sunat-alert sunat-alert-info">
            No tienes comprobantes registrados aún. <a href="?view=new-invoice">Emite tu primer comprobante</a>.
        </div>
    <?php endif; ?>
</div>

<script>
function sunatResendInvoice(invoiceId) {
    if (!confirm('¿Reenviar este comprobante a SUNAT?')) {
        return;
    }

    // Aquí iría la llamada AJAX para reenviar
    alert('Función de reenvío en desarrollo');
}
</script>
