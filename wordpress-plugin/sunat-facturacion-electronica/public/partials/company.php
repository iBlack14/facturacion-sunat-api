<div class="sunat-panel">
    <div class="sunat-panel-header">
        <h2>Datos de Mi Empresa</h2>
    </div>

    <!-- Menú de navegación -->
    <nav class="sunat-nav">
        <a href="?view=dashboard">Dashboard</a>
        <a href="?view=company" class="active">Mi Empresa</a>
        <a href="?view=certificate">Certificado</a>
        <a href="?view=clients">Clientes</a>
        <a href="?view=invoices">Comprobantes</a>
        <a href="?view=new-invoice" class="sunat-btn-primary">Nueva Factura</a>
    </nav>

    <?php if (isset($success_message)): ?>
        <div class="sunat-alert sunat-alert-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="sunat-form">
        <?php wp_nonce_field('sunat_company'); ?>

        <div class="sunat-form-section">
            <h3>Información Básica</h3>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="ruc">RUC *</label>
                    <input type="text" name="ruc" id="ruc" value="<?php echo $company ? esc_attr($company->ruc) : ''; ?>" maxlength="11" required>
                </div>

                <div class="sunat-form-field">
                    <label for="razon_social">Razón Social *</label>
                    <input type="text" name="razon_social" id="razon_social" value="<?php echo $company ? esc_attr($company->razon_social) : ''; ?>" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="nombre_comercial">Nombre Comercial</label>
                    <input type="text" name="nombre_comercial" id="nombre_comercial" value="<?php echo $company ? esc_attr($company->nombre_comercial) : ''; ?>">
                </div>
            </div>
        </div>

        <div class="sunat-form-section">
            <h3>Dirección</h3>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="direccion">Dirección *</label>
                    <input type="text" name="direccion" id="direccion" value="<?php echo $company ? esc_attr($company->direccion) : ''; ?>" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="departamento">Departamento *</label>
                    <input type="text" name="departamento" id="departamento" value="<?php echo $company ? esc_attr($company->departamento) : 'LIMA'; ?>" required>
                </div>

                <div class="sunat-form-field">
                    <label for="provincia">Provincia *</label>
                    <input type="text" name="provincia" id="provincia" value="<?php echo $company ? esc_attr($company->provincia) : 'LIMA'; ?>" required>
                </div>

                <div class="sunat-form-field">
                    <label for="distrito">Distrito *</label>
                    <input type="text" name="distrito" id="distrito" value="<?php echo $company ? esc_attr($company->distrito) : 'LIMA'; ?>" required>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="ubigeo">Ubigeo</label>
                    <input type="text" name="ubigeo" id="ubigeo" value="<?php echo $company ? esc_attr($company->ubigeo) : '150101'; ?>" maxlength="6">
                    <small>Código de ubigeo (ej: 150101 para Lima)</small>
                </div>
            </div>
        </div>

        <div class="sunat-form-section">
            <h3>Credenciales SOL</h3>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="usuario_sol">Usuario SOL</label>
                    <input type="text" name="usuario_sol" id="usuario_sol" value="<?php echo $company ? esc_attr($company->usuario_sol) : ''; ?>">
                    <small>Usuario de Clave SOL (opcional)</small>
                </div>

                <div class="sunat-form-field">
                    <label for="clave_sol">Clave SOL</label>
                    <input type="password" name="clave_sol" id="clave_sol" value="<?php echo $company ? esc_attr($company->clave_sol) : ''; ?>">
                    <small>Clave SOL (opcional)</small>
                </div>
            </div>
        </div>

        <div class="sunat-form-section">
            <h3>Configuración de Comprobantes</h3>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="modo">Modo de Operación *</label>
                    <select name="modo" id="modo" required>
                        <option value="beta" <?php echo ($company && $company->modo === 'beta') ? 'selected' : ''; ?>>Beta (Pruebas)</option>
                        <option value="produccion" <?php echo ($company && $company->modo === 'produccion') ? 'selected' : ''; ?>>Producción</option>
                    </select>
                    <small>Selecciona "Beta" para pruebas, "Producción" para comprobantes reales</small>
                </div>
            </div>

            <div class="sunat-form-row">
                <div class="sunat-form-field">
                    <label for="serie_factura">Serie Facturas</label>
                    <input type="text" name="serie_factura" id="serie_factura" value="<?php echo $company ? esc_attr($company->serie_factura) : 'F001'; ?>" maxlength="4">
                    <small>Serie para facturas (ej: F001)</small>
                </div>

                <div class="sunat-form-field">
                    <label for="serie_boleta">Serie Boletas</label>
                    <input type="text" name="serie_boleta" id="serie_boleta" value="<?php echo $company ? esc_attr($company->serie_boleta) : 'B001'; ?>" maxlength="4">
                    <small>Serie para boletas (ej: B001)</small>
                </div>
            </div>
        </div>

        <div class="sunat-form-actions">
            <button type="submit" name="sunat_save_company" class="sunat-btn sunat-btn-primary">
                Guardar Datos de Empresa
            </button>
            <a href="?view=dashboard" class="sunat-btn sunat-btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
