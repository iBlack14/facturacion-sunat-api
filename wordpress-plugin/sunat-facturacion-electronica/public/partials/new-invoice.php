<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Emitir Nueva Factura/Boleta</h2>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard">Dashboard</a>
        <a href="?view=company">Mi Empresa</a>
        <a href="?view=certificate">Certificado</a>
        <a href="?view=clients">Clientes</a>
        <a href="?view=invoices">Comprobantes</a>
        <a href="?view=new-invoice" class="active sunat-btn-primary">Nueva Factura</a>
    </nav>

    <?php if (isset($success_message)): ?>
        <div class="sunat-alert sunat-alert-success">
            <?php echo esc_html($success_message); ?>
            <?php if (isset($invoice_id)): ?>
                <p>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sunat_download_pdf&invoice_id=' . $invoice_id . '&nonce=' . wp_create_nonce('sunat_download_pdf')); ?>"
                       class="sunat-btn sunat-btn-primary" target="_blank">
                        Descargar PDF
                    </a>
                    <a href="?view=invoices" class="sunat-btn sunat-btn-secondary">Ver comprobantes</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="sunat-alert sunat-alert-danger">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="sunat-form" id="sunat-invoice-form">
        <?php wp_nonce_field('sunat_emit_invoice'); ?>

        <!-- Datos del Cliente -->
        <div class="sunat-form-section">
            <h3>Datos del Cliente</h3>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="client_select">Seleccionar Cliente (Opcional)</label>
                    <select id="client_select" class="sunat-client-select">
                        <option value="">Ingresar datos manualmente...</option>
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->id; ?>"
                                        data-tipo="<?php echo esc_attr($client->tipo_documento); ?>"
                                        data-numero="<?php echo esc_attr($client->numero_documento); ?>"
                                        data-razon="<?php echo esc_attr($client->razon_social); ?>"
                                        data-direccion="<?php echo esc_attr($client->direccion); ?>"
                                        data-email="<?php echo esc_attr($client->email); ?>">
                                    <?php echo esc_html($client->razon_social . ' - ' . $client->numero_documento); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="client_tipo_documento">Tipo de Documento *</label>
                    <select name="client_tipo_documento" id="client_tipo_documento" required>
                        <option value="">Seleccionar...</option>
                        <option value="1">DNI</option>
                        <option value="6">RUC</option>
                        <option value="4">Carnet de Extranjería</option>
                        <option value="7">Pasaporte</option>
                    </select>
                </div>

                <div class="sunat-form-field">
                    <label for="client_numero_documento">Número de Documento *</label>
                    <input type="text" name="client_numero_documento" id="client_numero_documento" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="client_razon_social">Razón Social / Nombre *</label>
                    <input type="text" name="client_razon_social" id="client_razon_social" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="client_direccion">Dirección</label>
                    <input type="text" name="client_direccion" id="client_direccion">
                </div>

                <div class="sunat-form-field">
                    <label for="client_email">Email</label>
                    <input type="email" name="client_email" id="client_email">
                </div>
            </div>
        </div>

        <!-- Items del Comprobante -->
        <div class="sunat-form-section">
            <h3>Items del Comprobante</h3>

            <div id="invoice-items">
                <div class="invoice-item">
                    <div class="sunat-form-row">
                        <div class="sunat-form-field">
                            <label>Código</label>
                            <input type="text" name="items[0][codigo]" placeholder="PROD-001" required>
                        </div>

                        <div class="sunat-form-field" style="flex: 2;">
                            <label>Descripción *</label>
                            <input type="text" name="items[0][descripcion]" placeholder="Descripción del producto/servicio" required>
                        </div>

                        <div class="sunat-form-field">
                            <label>Unidad</label>
                            <select name="items[0][unidad]">
                                <option value="NIU">NIU - Unidad</option>
                                <option value="ZZ">ZZ - Servicio</option>
                                <option value="KGM">KGM - Kilogramo</option>
                                <option value="MTR">MTR - Metro</option>
                            </select>
                        </div>

                        <div class="sunat-form-field">
                            <label>Cantidad *</label>
                            <input type="number" name="items[0][cantidad]" value="1" step="0.01" min="0.01" required>
                        </div>

                        <div class="sunat-form-field">
                            <label>Precio Unit. *</label>
                            <input type="number" name="items[0][mto_valor_unitario]" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>

                        <div class="sunat-form-field">
                            <label>IGV</label>
                            <select name="items[0][tip_afe_igv]">
                                <option value="10">Gravado</option>
                                <option value="20">Exonerado</option>
                                <option value="30">Inafecto</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="sunat-btn sunat-btn-secondary" id="add-item-btn">
                + Agregar Item
            </button>
        </div>

        <!-- Observaciones -->
        <div class="sunat-form-section">
            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="observaciones">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales..."></textarea>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="sunat-form-actions">
            <button type="submit" name="sunat_emit_invoice" class="sunat-btn sunat-btn-primary sunat-btn-large">
                Emitir Comprobante
            </button>
            <a href="?view=invoices" class="sunat-btn sunat-btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
(function() {
    let itemCounter = 1;

    // Seleccionar cliente
    document.getElementById('client_select').addEventListener('change', function() {
        if (this.value) {
            const option = this.options[this.selectedIndex];
            document.getElementById('client_tipo_documento').value = option.dataset.tipo;
            document.getElementById('client_numero_documento').value = option.dataset.numero;
            document.getElementById('client_razon_social').value = option.dataset.razon;
            document.getElementById('client_direccion').value = option.dataset.direccion;
            document.getElementById('client_email').value = option.dataset.email;
        }
    });

    // Agregar item
    document.getElementById('add-item-btn').addEventListener('click', function() {
        const container = document.getElementById('invoice-items');
        const newItem = document.createElement('div');
        newItem.className = 'invoice-item';
        newItem.innerHTML = `
            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <input type="text" name="items[${itemCounter}][codigo]" placeholder="PROD-001" required>
                </div>
                <div class="sunat-form-field" style="flex: 2;">
                    <input type="text" name="items[${itemCounter}][descripcion]" placeholder="Descripción" required>
                </div>
                <div class="sunat-form-field">
                    <select name="items[${itemCounter}][unidad]">
                        <option value="NIU">NIU</option>
                        <option value="ZZ">ZZ</option>
                        <option value="KGM">KGM</option>
                        <option value="MTR">MTR</option>
                    </select>
                </div>
                <div class="sunat-form-field">
                    <input type="number" name="items[${itemCounter}][cantidad]" value="1" step="0.01" min="0.01" required>
                </div>
                <div class="sunat-form-field">
                    <input type="number" name="items[${itemCounter}][mto_valor_unitario]" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
                <div class="sunat-form-field">
                    <select name="items[${itemCounter}][tip_afe_igv]">
                        <option value="10">Gravado</option>
                        <option value="20">Exonerado</option>
                        <option value="30">Inafecto</option>
                    </select>
                </div>
                <button type="button" class="sunat-btn-remove" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        container.appendChild(newItem);
        itemCounter++;
    });
})();
</script>
