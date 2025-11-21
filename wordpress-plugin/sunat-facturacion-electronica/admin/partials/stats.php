<div class="wrap">
    <h1>Estadísticas de Facturación</h1>

    <div class="sunat-stats-cards">
        <div class="sunat-stat-card">
            <h3>Total Comprobantes</h3>
            <p class="sunat-stat-number"><?php echo number_format($total); ?></p>
        </div>

        <div class="sunat-stat-card success">
            <h3>Aceptados</h3>
            <p class="sunat-stat-number"><?php echo number_format($aceptados); ?></p>
            <p class="sunat-stat-percent"><?php echo $total > 0 ? round(($aceptados / $total) * 100, 1) : 0; ?>%</p>
        </div>

        <div class="sunat-stat-card warning">
            <h3>Pendientes</h3>
            <p class="sunat-stat-number"><?php echo number_format($pendientes); ?></p>
            <p class="sunat-stat-percent"><?php echo $total > 0 ? round(($pendientes / $total) * 100, 1) : 0; ?>%</p>
        </div>

        <div class="sunat-stat-card danger">
            <h3>Rechazados</h3>
            <p class="sunat-stat-number"><?php echo number_format($rechazados); ?></p>
            <p class="sunat-stat-percent"><?php echo $total > 0 ? round(($rechazados / $total) * 100, 1) : 0; ?>%</p>
        </div>
    </div>

    <hr>

    <h2>Comprobantes por Usuario (Top 10)</h2>
    <?php if (!empty($stats_by_user)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Total Comprobantes</th>
                    <th>Aceptados</th>
                    <th>Tasa de Éxito</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats_by_user as $stat): ?>
                    <tr>
                        <td><strong><?php echo esc_html($stat->display_name); ?></strong></td>
                        <td><?php echo number_format($stat->total); ?></td>
                        <td><?php echo number_format($stat->aceptados); ?></td>
                        <td>
                            <?php
                            $rate = $stat->total > 0 ? round(($stat->aceptados / $stat->total) * 100, 1) : 0;
                            $color = $rate >= 90 ? 'green' : ($rate >= 70 ? 'orange' : 'red');
                            ?>
                            <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $rate; ?>%</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay datos disponibles</p>
    <?php endif; ?>

    <hr>

    <h2>Comprobantes por Mes (Últimos 6 meses)</h2>
    <?php if (!empty($stats_by_month)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Total Comprobantes</th>
                    <th>Monto Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats_by_month as $stat): ?>
                    <tr>
                        <td><strong><?php echo esc_html(date('F Y', strtotime($stat->mes . '-01'))); ?></strong></td>
                        <td><?php echo number_format($stat->total); ?></td>
                        <td>S/ <?php echo number_format($stat->monto_total, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay datos disponibles</p>
    <?php endif; ?>
</div>

<style>
.sunat-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.sunat-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sunat-stat-card.success {
    border-left: 4px solid #46b450;
}

.sunat-stat-card.warning {
    border-left: 4px solid #ffb900;
}

.sunat-stat-card.danger {
    border-left: 4px solid #dc3232;
}

.sunat-stat-card h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #646970;
    text-transform: uppercase;
}

.sunat-stat-number {
    font-size: 32px;
    font-weight: bold;
    margin: 0;
    color: #1d2327;
}

.sunat-stat-percent {
    font-size: 14px;
    color: #646970;
    margin: 5px 0 0;
}
</style>
