(function($) {
    'use strict';

    /**
     * Admin JavaScript para SUNAT Facturación
     *
     * @since 1.0.0
     */

    $(document).ready(function() {

        // Validación de formulario de configuración
        $('#sunat_api_email, #sunat_api_password').on('blur', function() {
            validateApiCredentials();
        });

        // Confirmar antes de eliminar
        $('.sunat-delete-action').on('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-actualizar estado de comprobantes pendientes
        if ($('.sunat-pending-invoices').length > 0) {
            setInterval(checkPendingInvoices, 30000); // Cada 30 segundos
        }

    });

    /**
     * Validar credenciales de API
     */
    function validateApiCredentials() {
        var email = $('#sunat_api_email').val();
        var password = $('#sunat_api_password').val();

        if (email && password) {
            // Aquí se podría agregar una validación AJAX
            console.log('Validating API credentials...');
        }
    }

    /**
     * Verificar comprobantes pendientes
     */
    function checkPendingInvoices() {
        $.ajax({
            url: sunatAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sunat_check_pending_invoices',
                nonce: sunatAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.updated > 0) {
                    console.log('Actualizados ' + response.data.updated + ' comprobantes');
                    // Opcional: recargar la página o actualizar tabla
                }
            }
        });
    }

    /**
     * Emitir comprobante manualmente (WooCommerce)
     */
    window.sunatEmitInvoice = function(orderId) {
        if (!confirm('¿Emitir comprobante para este pedido?')) {
            return;
        }

        $.ajax({
            url: sunatAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sunat_emit_invoice_manual',
                order_id: orderId,
                nonce: sunatAdmin.nonce
            },
            beforeSend: function() {
                $('.sunat-invoice-info').prepend('<p class="sunat-loading">Emitiendo comprobante...</p>');
            },
            success: function(response) {
                $('.sunat-loading').remove();

                if (response.success) {
                    alert('Comprobante emitido exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                $('.sunat-loading').remove();
                alert('Error de conexión al servidor');
            }
        });
    };

    /**
     * Reenviar comprobante a SUNAT (WooCommerce)
     */
    window.sunatResendToSunat = function(invoiceId, orderId) {
        if (!confirm('¿Reenviar este comprobante a SUNAT?')) {
            return;
        }

        $.ajax({
            url: sunatAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sunat_resend_invoice',
                invoice_id: invoiceId,
                order_id: orderId,
                nonce: sunatAdmin.nonce
            },
            beforeSend: function() {
                $('.sunat-invoice-info').prepend('<p class="sunat-loading">Reenviando a SUNAT...</p>');
            },
            success: function(response) {
                $('.sunat-loading').remove();

                if (response.success) {
                    alert('Comprobante reenviado exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                $('.sunat-loading').remove();
                alert('Error de conexión al servidor');
            }
        });
    };

    /**
     * Copiar al portapapeles
     */
    window.sunatCopyToClipboard = function(text) {
        var temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();

        alert('Copiado al portapapeles');
    };

})(jQuery);
