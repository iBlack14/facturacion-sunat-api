<div class="wrap">
    <h1>Logs del Sistema</h1>

    <!-- Filtros -->
    <div class="sunat-logs-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="sunat-facturacion-logs">

            <select name="level">
                <option value="">Todos los niveles</option>
                <option value="info" <?php selected(isset($_GET['level']) && $_GET['level'] === 'info'); ?>>Info</option>
                <option value="warning" <?php selected(isset($_GET['level']) && $_GET['level'] === 'warning'); ?>>Warning</option>
                <option value="error" <?php selected(isset($_GET['level']) && $_GET['level'] === 'error'); ?>>Error</option>
            </select>

            <input type="submit" class="button" value="Filtrar">
            <a href="<?php echo admin_url('admin.php?page=sunat-facturacion-logs'); ?>" class="button">Limpiar filtros</a>
        </form>
    </div>

    <!-- Tabla de logs -->
    <?php if (!empty($logs)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 150px;">Fecha</th>
                    <th style="width: 120px;">Usuario</th>
                    <th style="width: 100px;">Nivel</th>
                    <th style="width: 150px;">Acción</th>
                    <th>Mensaje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date('d/m/Y H:i:s', strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html($log->display_name ?? 'Sistema'); ?></td>
                        <td>
                            <?php
                            $badge_class = 'default';
                            if ($log->level === 'error') {
                                $badge_class = 'error';
                            } elseif ($log->level === 'warning') {
                                $badge_class = 'warning';
                            } elseif ($log->level === 'info') {
                                $badge_class = 'info';
                            }
                            ?>
                            <span class="sunat-badge sunat-badge-<?php echo $badge_class; ?>"><?php echo esc_html($log->level); ?></span>
                        </td>
                        <td><code><?php echo esc_html($log->action); ?></code></td>
                        <td><?php echo esc_html($log->message); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $current_url = remove_query_arg('paged');
                    $pagination = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    ]);
                    echo $pagination;
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="notice notice-info">
            <p>No hay logs registrados con los filtros seleccionados.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.sunat-logs-filters {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.sunat-logs-filters form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.sunat-logs-filters select {
    min-width: 200px;
}

.sunat-badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    border-radius: 3px;
}

.sunat-badge-info {
    background: #d5e5f7;
    color: #0073aa;
}

.sunat-badge-warning {
    background: #fff8e5;
    color: #826200;
}

.sunat-badge-error {
    background: #fbeaea;
    color: #dc3232;
}

.sunat-badge-default {
    background: #f0f0f1;
    color: #50575e;
}
</style>
