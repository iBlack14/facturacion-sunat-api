<?php
/**
 * Gestión de certificados digitales
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Certificate_Manager {

    /**
     * Directorio de certificados
     *
     * @since 1.0.0
     * @var string
     */
    private $certificates_dir;

    /**
     * Extensiones permitidas
     *
     * @since 1.0.0
     * @var array
     */
    private $allowed_extensions = ['pfx', 'p12', 'pem'];

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->certificates_dir = $upload_dir['basedir'] . '/sunat-certificados';

        // Crear directorio si no existe
        if (!file_exists($this->certificates_dir)) {
            wp_mkdir_p($this->certificates_dir);
            $this->protect_directory();
        }
    }

    /**
     * Proteger directorio con .htaccess
     *
     * @since 1.0.0
     */
    private function protect_directory() {
        $htaccess_file = $this->certificates_dir . '/.htaccess';
        $htaccess_content = "deny from all\n";

        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Agregar index.php vacío
        $index_file = $this->certificates_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Subir certificado
     *
     * @since 1.0.0
     * @param array $file Archivo $_FILES
     * @param string $password Contraseña del certificado
     * @param int $user_id ID del usuario
     * @return array
     */
    public function upload_certificate($file, $password, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Validar archivo
        $validation = $this->validate_certificate_file($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Obtener extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Generar nombre único
        $filename = 'cert_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $this->certificates_dir . '/' . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'message' => 'Error al guardar el certificado en el servidor'
            ];
        }

        // Validar que el certificado sea válido con la contraseña
        $cert_validation = $this->validate_certificate_password($filepath, $password, $extension);
        if (!$cert_validation['success']) {
            // Eliminar archivo si la contraseña es incorrecta
            unlink($filepath);
            return $cert_validation;
        }

        // Extraer información del certificado
        $cert_info = $cert_validation['info'];

        // Cifrar contraseña
        $encrypted_password = $this->encrypt_password($password);

        // Guardar en base de datos
        global $wpdb;
        $table = $wpdb->prefix . 'sunat_certificates';

        // Desactivar certificados anteriores del usuario
        $wpdb->update(
            $table,
            ['active' => 0],
            ['user_id' => $user_id]
        );

        // Insertar nuevo certificado
        $result = $wpdb->insert($table, [
            'user_id' => $user_id,
            'filename' => $filename,
            'filepath' => $filepath,
            'password_encrypted' => $encrypted_password,
            'expires_at' => $cert_info['valid_to'],
            'active' => 1,
            'uploaded_at' => current_time('mysql')
        ]);

        if ($result === false) {
            // Eliminar archivo si falla el guardado en BD
            unlink($filepath);
            return [
                'success' => false,
                'message' => 'Error al guardar el certificado en la base de datos'
            ];
        }

        return [
            'success' => true,
            'message' => 'Certificado subido exitosamente',
            'certificate_id' => $wpdb->insert_id,
            'expires_at' => $cert_info['valid_to'],
            'subject' => $cert_info['subject']
        ];
    }

    /**
     * Validar archivo de certificado
     *
     * @since 1.0.0
     * @param array $file
     * @return array
     */
    private function validate_certificate_file($file) {
        // Verificar que se subió correctamente
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Error al subir el archivo'
            ];
        }

        // Verificar tamaño (máximo 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande (máximo 5MB)'
            ];
        }

        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowed_extensions)) {
            return [
                'success' => false,
                'message' => 'Extensión no permitida. Use: ' . implode(', ', $this->allowed_extensions)
            ];
        }

        return ['success' => true];
    }

    /**
     * Validar certificado y contraseña
     *
     * @since 1.0.0
     * @param string $filepath
     * @param string $password
     * @param string $extension
     * @return array
     */
    private function validate_certificate_password($filepath, $password, $extension) {
        if ($extension === 'pem') {
            // Para PEM, intentar leer el certificado
            $cert_content = file_get_contents($filepath);
            $cert = openssl_x509_read($cert_content);

            if ($cert === false) {
                return [
                    'success' => false,
                    'message' => 'El archivo PEM no es un certificado válido'
                ];
            }

            $cert_data = openssl_x509_parse($cert);
            openssl_x509_free($cert);

        } else {
            // Para PFX/P12, validar con contraseña
            $cert_content = file_get_contents($filepath);
            $certs = [];

            if (!openssl_pkcs12_read($cert_content, $certs, $password)) {
                return [
                    'success' => false,
                    'message' => 'Contraseña incorrecta o certificado inválido'
                ];
            }

            // Extraer información del certificado
            $cert = openssl_x509_read($certs['cert']);
            $cert_data = openssl_x509_parse($cert);
            openssl_x509_free($cert);
        }

        // Verificar que no haya expirado
        $valid_to = $cert_data['validTo_time_t'];
        if ($valid_to < time()) {
            return [
                'success' => false,
                'message' => 'El certificado ha expirado'
            ];
        }

        return [
            'success' => true,
            'info' => [
                'subject' => $cert_data['subject']['CN'] ?? 'No disponible',
                'valid_from' => date('Y-m-d H:i:s', $cert_data['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $cert_data['validTo_time_t'])
            ]
        ];
    }

    /**
     * Cifrar contraseña
     *
     * @since 1.0.0
     * @param string $password
     * @return string
     */
    private function encrypt_password($password) {
        // Usar wp_salt como clave
        $key = wp_salt('auth');
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);

        // Retornar IV + encrypted concatenados en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Descifrar contraseña
     *
     * @since 1.0.0
     * @param string $encrypted_password
     * @return string
     */
    public function decrypt_password($encrypted_password) {
        $key = wp_salt('auth');
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');

        $data = base64_decode($encrypted_password);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Obtener certificado activo del usuario
     *
     * @since 1.0.0
     * @param int $user_id
     * @return object|null
     */
    public function get_active_certificate($user_id = null) {
        return Sunat_Facturacion_Database::get_user_certificate($user_id);
    }

    /**
     * Obtener datos del certificado para uso
     *
     * @since 1.0.0
     * @param int $user_id
     * @return array|null
     */
    public function get_certificate_for_use($user_id = null) {
        $cert = $this->get_active_certificate($user_id);

        if (!$cert) {
            return null;
        }

        // Verificar que el archivo existe
        if (!file_exists($cert->filepath)) {
            return null;
        }

        // Descifrar contraseña
        $password = $this->decrypt_password($cert->password_encrypted);

        return [
            'filepath' => $cert->filepath,
            'password' => $password,
            'filename' => $cert->filename,
            'expires_at' => $cert->expires_at
        ];
    }

    /**
     * Eliminar certificado
     *
     * @since 1.0.0
     * @param int $certificate_id
     * @param int $user_id
     * @return array
     */
    public function delete_certificate($certificate_id, $user_id = null) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'sunat_certificates';

        // Obtener certificado
        $cert = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $certificate_id,
            $user_id
        ));

        if (!$cert) {
            return [
                'success' => false,
                'message' => 'Certificado no encontrado'
            ];
        }

        // Eliminar archivo
        if (file_exists($cert->filepath)) {
            unlink($cert->filepath);
        }

        // Eliminar de base de datos
        $wpdb->delete($table, ['id' => $certificate_id]);

        return [
            'success' => true,
            'message' => 'Certificado eliminado exitosamente'
        ];
    }

    /**
     * Verificar si el certificado está próximo a vencer
     *
     * @since 1.0.0
     * @param int $user_id
     * @param int $days Días de anticipación
     * @return bool
     */
    public function is_certificate_expiring_soon($user_id = null, $days = 30) {
        $cert = $this->get_active_certificate($user_id);

        if (!$cert) {
            return false;
        }

        $expires_at = strtotime($cert->expires_at);
        $warning_date = strtotime("+{$days} days");

        return $expires_at <= $warning_date;
    }

    /**
     * Obtener días restantes de validez del certificado
     *
     * @since 1.0.0
     * @param int $user_id
     * @return int|null
     */
    public function get_certificate_days_remaining($user_id = null) {
        $cert = $this->get_active_certificate($user_id);

        if (!$cert) {
            return null;
        }

        $expires_at = strtotime($cert->expires_at);
        $now = time();

        $diff = $expires_at - $now;
        return max(0, floor($diff / (60 * 60 * 24)));
    }
}
