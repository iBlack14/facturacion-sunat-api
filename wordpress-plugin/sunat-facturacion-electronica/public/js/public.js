(function($) {
    'use strict';

    /**
     * Public JavaScript para SUNAT Facturación
     *
     * @since 1.0.0
     */

    $(document).ready(function() {

        // Validación de RUC/DNI
        $('#client_tipo_documento, #client_numero_documento').on('blur', function() {
            validateClientDocument();
        });

        // Auto-completar cliente
        $('#client_select').on('change', function() {
            if ($(this).val()) {
                const option = $(this).find('option:selected');
                $('#client_tipo_documento').val(option.data('tipo'));
                $('#client_numero_documento').val(option.data('numero'));
                $('#client_razon_social').val(option.data('razon'));
                $('#client_direccion').val(option.data('direccion'));
                $('#client_email').val(option.data('email'));
            }
        });

        // Calcular totales en nueva factura
        $(document).on('change', 'input[name*="[cantidad]"], input[name*="[mto_valor_unitario]"]', function() {
            calculateInvoiceTotal();
        });

        // Validar formulario antes de enviar
        $('#sunat-invoice-form').on('submit', function(e) {
            if (!validateInvoiceForm()) {
                e.preventDefault();
                return false;
            }
        });

    });

    /**
     * Validar documento del cliente
     */
    function validateClientDocument() {
        const tipo = $('#client_tipo_documento').val();
        const numero = $('#client_numero_documento').val();

        if (!tipo || !numero) {
            return;
        }

        let isValid = true;
        let message = '';

        if (tipo === '1' && numero.length !== 8) {
            isValid = false;
            message = 'El DNI debe tener 8 dígitos';
        } else if (tipo === '6' && numero.length !== 11) {
            isValid = false;
            message = 'El RUC debe tener 11 dígitos';
        }

        if (!isValid) {
            alert(message);
            $('#client_numero_documento').focus();
        }
    }

    /**
     * Calcular total de la factura
     */
    function calculateInvoiceTotal() {
        let subtotal = 0;

        $('.invoice-item').each(function() {
            const cantidad = parseFloat($(this).find('input[name*="[cantidad]"]').val()) || 0;
            const precioUnit = parseFloat($(this).find('input[name*="[mto_valor_unitario]"]').val()) || 0;
            subtotal += cantidad * precioUnit;
        });

        const igv = subtotal * 0.18;
        const total = subtotal + igv;

        // Mostrar totales (si existe un elemento para mostrarlos)
        if ($('#invoice-totals').length) {
            $('#invoice-subtotal').text('S/ ' + subtotal.toFixed(2));
            $('#invoice-igv').text('S/ ' + igv.toFixed(2));
            $('#invoice-total').text('S/ ' + total.toFixed(2));
        }
    }

    /**
     * Validar formulario de factura
     */
    function validateInvoiceForm() {
        // Verificar que haya al menos un item
        if ($('.invoice-item').length === 0) {
            alert('Debes agregar al menos un item');
            return false;
        }

        // Verificar que todos los items tengan datos válidos
        let valid = true;
        $('.invoice-item').each(function() {
            const descripcion = $(this).find('input[name*="[descripcion]"]').val();
            const cantidad = parseFloat($(this).find('input[name*="[cantidad]"]').val());
            const precio = parseFloat($(this).find('input[name*="[mto_valor_unitario]"]').val());

            if (!descripcion || cantidad <= 0 || precio <= 0) {
                alert('Todos los items deben tener descripción, cantidad y precio válidos');
                valid = false;
                return false;
            }
        });

        return valid;
    }

    /**
     * Reenviar comprobante a SUNAT
     */
    window.sunatResendInvoice = function(invoiceId) {
        if (!confirm('¿Reenviar este comprobante a SUNAT?')) {
            return;
        }

        $.ajax({
            url: sunatPublic.ajaxurl,
            type: 'POST',
            data: {
                action: 'sunat_resend_invoice_public',
                invoice_id: invoiceId,
                nonce: sunatPublic.nonce
            },
            beforeSend: function() {
                alert('Reenviando a SUNAT...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Comprobante reenviado exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error de conexión al servidor');
            }
        });
    };

    /**
     * Copiar al portapapeles
     */
    window.sunatCopyToClipboard = function(text) {
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();

        alert('Copiado al portapapeles');
    };

})(jQuery);
