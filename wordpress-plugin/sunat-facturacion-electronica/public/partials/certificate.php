<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Certificado Digital</h2>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard">Dashboard</a>
        <a href="?view=company">Mi Empresa</a>
        <a href="?view=certificate" class="active">Certificado</a>
        <a href="?view=clients">Clientes</a>
        <a href="?view=invoices">Comprobantes</a>
        <a href="?view=new-invoice" class="sunat-btn-primary">Nueva Factura</a>
    </nav>

    <?php if (isset($success_message)): ?>
        <div class="sunat-alert sunat-alert-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="sunat-alert sunat-alert-danger">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Certificado actual -->
    <?php if ($certificate): ?>
        <div class="sunat-certificate-info">
            <h3>Certificado Activo</h3>
            <table class="sunat-table">
                <tr>
                    <td><strong>Archivo:</strong></td>
                    <td><?php echo esc_html($certificate->filename); ?></td>
                </tr>
                <tr>
                    <td><strong>Fecha de Subida:</strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($certificate->uploaded_at)); ?></td>
                </tr>
                <tr>
                    <td><strong>Fecha de Vencimiento:</strong></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($certificate->expires_at)); ?>
                        <?php if ($days_remaining !== null): ?>
                            <?php if ($days_remaining < 30): ?>
                                <span class="sunat-badge sunat-badge-danger">Vence en <?php echo $days_remaining; ?> días</span>
                            <?php elseif ($days_remaining < 90): ?>
                                <span class="sunat-badge sunat-badge-warning">Vence en <?php echo $days_remaining; ?> días</span>
                            <?php else: ?>
                                <span class="sunat-badge sunat-badge-success">Vence en <?php echo $days_remaining; ?> días</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Estado:</strong></td>
                    <td>
                        <?php if ($certificate->active): ?>
                            <span class="sunat-badge sunat-badge-success">Activo</span>
                        <?php else: ?>
                            <span class="sunat-badge sunat-badge-default">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div class="sunat-form-actions">
                <a href="<?php echo wp_nonce_url('?view=certificate&delete_cert=' . $certificate->id, 'sunat_delete_cert_' . $certificate->id); ?>"
                   class="sunat-btn sunat-btn-danger"
                   onclick="return confirm('¿Estás seguro de eliminar este certificado?')">
                    Eliminar Certificado
                </a>
            </div>
        </div>

        <hr>

        <h3>Subir Nuevo Certificado</h3>
        <p>Al subir un nuevo certificado, el certificado actual será reemplazado.</p>
    <?php else: ?>
        <div class="sunat-alert sunat-alert-info">
            <strong>ℹ Certificado Digital:</strong> Para emitir comprobantes electrónicos necesitas subir tu certificado digital (.pfx o .pem) otorgado por una entidad certificadora autorizada por SUNAT.
        </div>
    <?php endif; ?>

    <!-- Formulario de subida -->
    <form method="post" enctype="multipart/form-data" class="sunat-form">
        <?php wp_nonce_field('sunat_certificate'); ?>

        <div class="sunat-form-section">
            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="certificate">Archivo de Certificado *</label>
                    <input type="file" name="certificate" id="certificate" accept=".pfx,.p12,.pem" required>
                    <small>Formatos soportados: .pfx, .p12, .pem (Máximo 5MB)</small>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="certificate_password">Contraseña del Certificado *</label>
                    <input type="password" name="certificate_password" id="certificate_password" required autocomplete="off">
                    <small>Contraseña con la que protegiste tu certificado</small>
                </div>
            </div>
        </div>

        <div class="sunat-form-actions">
            <button type="submit" name="sunat_upload_certificate" class="sunat-btn sunat-btn-primary">
                Subir Certificado
            </button>
            <a href="?view=dashboard" class="sunat-btn sunat-btn-secondary">Cancelar</a>
        </div>
    </form>

    <!-- Información adicional -->
    <div class="sunat-info-section">
        <h3>¿Cómo obtener un certificado digital?</h3>
        <p>Puedes obtener tu certificado digital en las siguientes entidades certificadoras autorizadas por SUNAT:</p>
        <ul>
            <li><a href="https://www.llama.pe" target="_blank">Llama.pe</a></li>
            <li><a href="https://www.eCert.gob.pe" target="_blank">eCert - RENIEC</a></li>
            <li><a href="https://www.globalsign.com" target="_blank">GlobalSign</a></li>
        </ul>
    </div>
</div>
