<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Gestión de Clientes</h2>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard">Dashboard</a>
        <a href="?view=company">Mi Empresa</a>
        <a href="?view=certificate">Certificado</a>
        <a href="?view=clients" class="active">Clientes</a>
        <a href="?view=invoices">Comprobantes</a>
        <a href="?view=new-invoice" class="sunat-btn-primary">Nueva Factura</a>
    </nav>

    <?php if (isset($success_message)): ?>
        <div class="sunat-alert sunat-alert-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de cliente -->
    <div class="sunat-form-container">
        <h3><?php echo $editing_client ? 'Editar Cliente' : 'Nuevo Cliente'; ?></h3>

        <form method="post" class="sunat-form">
            <?php wp_nonce_field('sunat_client'); ?>

            <?php if ($editing_client): ?>
                <input type="hidden" name="client_id" value="<?php echo esc_attr($editing_client->id); ?>">
            <?php endif; ?>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="tipo_documento">Tipo de Documento *</label>
                    <select name="tipo_documento" id="tipo_documento" required>
                        <option value="">Seleccionar...</option>
                        <option value="1" <?php echo ($editing_client && $editing_client->tipo_documento === '1') ? 'selected' : ''; ?>>DNI</option>
                        <option value="6" <?php echo ($editing_client && $editing_client->tipo_documento === '6') ? 'selected' : ''; ?>>RUC</option>
                        <option value="4" <?php echo ($editing_client && $editing_client->tipo_documento === '4') ? 'selected' : ''; ?>>Carnet de Extranjería</option>
                        <option value="7" <?php echo ($editing_client && $editing_client->tipo_documento === '7') ? 'selected' : ''; ?>>Pasaporte</option>
                    </select>
                </div>

                <div class="sunat-form-field">
                    <label for="numero_documento">Número de Documento *</label>
                    <input type="text" name="numero_documento" id="numero_documento"
                           value="<?php echo $editing_client ? esc_attr($editing_client->numero_documento) : ''; ?>" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="razon_social">Razón Social / Nombre Completo *</label>
                    <input type="text" name="razon_social" id="razon_social"
                           value="<?php echo $editing_client ? esc_attr($editing_client->razon_social) : ''; ?>" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                           value="<?php echo $editing_client ? esc_attr($editing_client->direccion) : ''; ?>">
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email"
                           value="<?php echo $editing_client ? esc_attr($editing_client->email) : ''; ?>">
                </div>

                <div class="sunat-form-field">
                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" id="telefono"
                           value="<?php echo $editing_client ? esc_attr($editing_client->telefono) : ''; ?>">
                </div>
            </div>

            <div class="sunat-form-actions">
                <button type="submit" name="sunat_save_client" class="sunat-btn sunat-btn-primary">
                    <?php echo $editing_client ? 'Actualizar Cliente' : 'Guardar Cliente'; ?>
                </button>
                <?php if ($editing_client): ?>
                    <a href="?view=clients" class="sunat-btn sunat-btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <hr>

    <!-- Lista de clientes -->
    <h3>Mis Clientes</h3>

    <?php if (!empty($clients)): ?>
        <table class="sunat-table">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Razón Social / Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <?php
                            $doc_types = ['1' => 'DNI', '6' => 'RUC', '4' => 'CE', '7' => 'PAS'];
                            echo esc_html($doc_types[$client->tipo_documento] ?? $client->tipo_documento);
                            ?>
                            - <?php echo esc_html($client->numero_documento); ?>
                        </td>
                        <td><strong><?php echo esc_html($client->razon_social); ?></strong></td>
                        <td><?php echo esc_html($client->email); ?></td>
                        <td><?php echo esc_html($client->telefono); ?></td>
                        <td>
                            <a href="?view=clients&edit=<?php echo $client->id; ?>" class="sunat-btn sunat-btn-small">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="sunat-alert sunat-alert-info">
            No tienes clientes registrados aún. Agrega tu primer cliente usando el formulario anterior.
        </div>
    <?php endif; ?>
</div>
