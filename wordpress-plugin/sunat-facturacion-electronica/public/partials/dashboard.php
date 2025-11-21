<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Panel de Facturación SUNAT</h2>
        <p>Bienvenido, <?php echo wp_get_current_user()->display_name; ?></p>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard" class="active">Dashboard</a>
        <a href="?view=company">Mi Empresa</a>
        <a href="?view=certificate">Certificado</a>
        <a href="?view=clients">Clientes</a>
        <a href="?view=invoices">Comprobantes</a>
        <a href="?view=new-invoice" class="sunat-btn-primary">Nueva Factura</a>
    </nav>

    <!-- Advertencias -->
    <?php if (!$company): ?>
        <div class="sunat-alert sunat-alert-warning">
            <strong>⚠ Configuración pendiente:</strong> Debes <a href="?view=company">configurar los datos de tu empresa</a> para poder emitir comprobantes.
        </div>
    <?php endif; ?>

    <?php if (!$certificate): ?>
        <div class="sunat-alert sunat-alert-warning">
            <strong>⚠ Certificado faltante:</strong> Debes <a href="?view=certificate">subir tu certificado digital</a> para poder emitir comprobantes.
        </div>
    <?php endif; ?>

    <?php if ($certificate_warning): ?>
        <div class="sunat-alert sunat-alert-danger">
            <strong>⚠ Certificado próximo a vencer:</strong> Tu certificado digital vence en <?php echo $days_remaining; ?> días. Por favor, renuévalo pronto.
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="sunat-stats-grid">
        <div class="sunat-stat-box">
            <div class="sunat-stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="sunat-stat-label">Total Comprobantes</div>
        </div>

        <div class="sunat-stat-box success">
            <div class="sunat-stat-value"><?php echo number_format($stats['aceptados']); ?></div>
            <div class="sunat-stat-label">Aceptados</div>
        </div>

        <div class="sunat-stat-box warning">
            <div class="sunat-stat-value"><?php echo number_format($stats['pendientes']); ?></div>
            <div class="sunat-stat-label">Pendientes</div>
        </div>

        <div class="sunat-stat-box danger">
            <div class="sunat-stat-value"><?php echo number_format($stats['rechazados']); ?></div>
            <div class="sunat-stat-label">Rechazados</div>
        </div>
    </div>

    <!-- Información de empresa y certificado -->
    <div class="sunat-info-grid">
        <div class="sunat-info-box">
            <h3>Mi Empresa</h3>
            <?php if ($company): ?>
                <p><strong>RUC:</strong> <?php echo esc_html($company->ruc); ?></p>
                <p><strong>Razón Social:</strong> <?php echo esc_html($company->razon_social); ?></p>
                <p><strong>Modo:</strong> <?php echo $company->modo === 'beta' ? 'Beta' : 'Producción'; ?></p>
                <p><a href="?view=company">Editar datos</a></p>
            <?php else: ?>
                <p>No has configurado tu empresa aún.</p>
                <p><a href="?view=company" class="sunat-btn">Configurar ahora</a></p>
            <?php endif; ?>
        </div>

        <div class="sunat-info-box">
            <h3>Certificado Digital</h3>
            <?php if ($certificate): ?>
                <p><strong>Archivo:</strong> <?php echo esc_html($certificate->filename); ?></p>
                <p><strong>Vence:</strong> <?php echo date('d/m/Y', strtotime($certificate->expires_at)); ?></p>
                <?php if ($days_remaining !== null): ?>
                    <p><strong>Días restantes:</strong> <?php echo $days_remaining; ?></p>
                <?php endif; ?>
                <p><a href="?view=certificate">Ver detalles</a></p>
            <?php else: ?>
                <p>No has subido tu certificado digital.</p>
                <p><a href="?view=certificate" class="sunat-btn">Subir certificado</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="sunat-quick-actions">
        <h3>Acciones Rápidas</h3>
        <div class="sunat-actions-grid">
            <a href="?view=new-invoice" class="sunat-action-card">
                <span class="dashicons dashicons-media-document"></span>
                <strong>Nueva Factura/Boleta</strong>
                <p>Emitir un nuevo comprobante</p>
            </a>

            <a href="?view=clients" class="sunat-action-card">
                <span class="dashicons dashicons-groups"></span>
                <strong>Gestionar Clientes</strong>
                <p>Ver y editar tus clientes</p>
            </a>

            <a href="?view=invoices" class="sunat-action-card">
                <span class="dashicons dashicons-list-view"></span>
                <strong>Ver Comprobantes</strong>
                <p>Historial de facturas y boletas</p>
            </a>
        </div>
    </div>
</div>
