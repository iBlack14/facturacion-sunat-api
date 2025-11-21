<div class="wrap">
    <h1>Configuración SUNAT Facturación</h1>

    <form method="post" action="">
        <?php wp_nonce_field('sunat_settings'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="api_url">URL de la API</label>
                    </th>
                    <td>
                        <input type="url" name="api_url" id="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" required>
                        <p class="description">URL base de la API de facturación SUNAT</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="api_email">Email API</label>
                    </th>
                    <td>
                        <input type="email" name="api_email" id="api_email" value="<?php echo esc_attr($api_email); ?>" class="regular-text" required>
                        <p class="description">Email de autenticación para la API</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="api_password">Contraseña API</label>
                    </th>
                    <td>
                        <input type="password" name="api_password" id="api_password" value="" class="regular-text" placeholder="Dejar en blanco para mantener la actual">
                        <p class="description">Contraseña de autenticación para la API</p>
                    </td>
                </tr>

                <?php if (class_exists('WooCommerce')): ?>
                <tr>
                    <th scope="row">
                        <label for="auto_emit_woocommerce">WooCommerce</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_emit_woocommerce" id="auto_emit_woocommerce" value="1" <?php checked($auto_emit, '1'); ?>>
                            Emitir comprobantes automáticamente al completar pedidos
                        </label>
                        <p class="description">Los comprobantes se emitirán cuando un pedido cambie a estado "Completado"</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="sunat_save_settings" class="button button-primary" value="Guardar Configuración">
        </p>
    </form>

    <hr>

    <h2>Información del Sistema</h2>
    <table class="widefat">
        <tbody>
            <tr>
                <td><strong>Versión del Plugin:</strong></td>
                <td><?php echo esc_html(SUNAT_FACTURACION_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong>PHP:</strong></td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td><strong>WordPress:</strong></td>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td><strong>OpenSSL:</strong></td>
                <td><?php echo extension_loaded('openssl') ? '✓ Habilitado' : '✗ No disponible'; ?></td>
            </tr>
            <tr>
                <td><strong>WooCommerce:</strong></td>
                <td><?php echo class_exists('WooCommerce') ? '✓ Instalado (' . WC()->version . ')' : '✗ No instalado'; ?></td>
            </tr>
        </tbody>
    </table>
</div>
