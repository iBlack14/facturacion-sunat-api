<?php
/**
 * Página de información para debugging
 * Acceder a: https://tu-dominio.com/info.php
 * ⚠️ ELIMINAR EN PRODUCCIÓN
 */

// Solo permitir en desarrollo
if (getenv('APP_ENV') === 'production' && getenv('APP_DEBUG') !== 'true') {
    http_response_code(403);
    die('Forbidden');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información del Sistema - Facturación SUNAT</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; margin-top: 0; }
        h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        .status.ok { background: #27ae60; color: white; }
        .status.error { background: #e74c3c; color: white; }
        .status.warning { background: #f39c12; color: white; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        tr:hover { background: #f8f9fa; }
        code { background: #ecf0f1; padding: 2px 6px; border-radius: 3px; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Información del Sistema - Facturación SUNAT</h1>

    <div class="card">
        <h2>📊 Estado General</h2>
        <table>
            <tr>
                <td><strong>PHP Version</strong></td>
                <td><?= phpversion() ?></td>
            </tr>
            <tr>
                <td><strong>APP_ENV</strong></td>
                <td><?= getenv('APP_ENV') ?: '<em>No configurado</em>' ?></td>
            </tr>
            <tr>
                <td><strong>APP_DEBUG</strong></td>
                <td><?= getenv('APP_DEBUG') ?: '<em>No configurado</em>' ?></td>
            </tr>
            <tr>
                <td><strong>APP_KEY</strong></td>
                <td>
                    <?php if (getenv('APP_KEY')): ?>
                        <span class="status ok">✅ Configurado</span>
                    <?php else: ?>
                        <span class="status error">❌ NO CONFIGURADO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Tiempo del servidor</strong></td>
                <td><?= date('Y-m-d H:i:s T') ?></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>🗄️ Base de Datos</h2>
        <table>
            <tr>
                <td><strong>Conexión</strong></td>
                <td><?= getenv('DB_CONNECTION') ?: 'mysql' ?></td>
            </tr>
            <tr>
                <td><strong>Host</strong></td>
                <td><?= getenv('DB_HOST') ?: 'No configurado' ?></td>
            </tr>
            <tr>
                <td><strong>Puerto</strong></td>
                <td><?= getenv('DB_PORT') ?: '3306' ?></td>
            </tr>
            <tr>
                <td><strong>Base de datos</strong></td>
                <td><?= getenv('DB_DATABASE') ?: 'No configurado' ?></td>
            </tr>
            <tr>
                <td><strong>Usuario</strong></td>
                <td><?= getenv('DB_USERNAME') ?: 'No configurado' ?></td>
            </tr>
            <tr>
                <td><strong>Estado de conexión</strong></td>
                <td>
                    <?php
                    try {
                        $pdo = new PDO(
                            getenv('DB_CONNECTION') . ':host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
                            getenv('DB_USERNAME'),
                            getenv('DB_PASSWORD')
                        );
                        echo '<span class="status ok">✅ Conectado</span>';
                    } catch (Exception $e) {
                        echo '<span class="status error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>🔧 Extensiones PHP Requeridas</h2>
        <table>
            <?php
            $required = ['pdo', 'pdo_mysql', 'mbstring', 'xml', 'intl', 'zip', 'gd', 'soap', 'bcmath'];
            foreach ($required as $ext):
                $loaded = extension_loaded($ext);
            ?>
            <tr>
                <td><code><?= $ext ?></code></td>
                <td>
                    <?php if ($loaded): ?>
                        <span class="status ok">✅ Instalado</span>
                    <?php else: ?>
                        <span class="status error">❌ NO instalado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>📁 Permisos de Directorios</h2>
        <table>
            <?php
            $dirs = [
                'storage/app' => '../storage/app',
                'storage/logs' => '../storage/logs',
                'storage/framework/cache' => '../storage/framework/cache',
                'bootstrap/cache' => '../bootstrap/cache',
            ];
            foreach ($dirs as $name => $path):
                $writable = is_writable($path);
            ?>
            <tr>
                <td><code><?= $name ?></code></td>
                <td>
                    <?php if ($writable): ?>
                        <span class="status ok">✅ Escribible</span>
                    <?php else: ?>
                        <span class="status error">❌ NO escribible</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>📋 Últimos Logs de Laravel</h2>
        <pre><?php
        $logFile = '../storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lastLines = array_slice($lines, -30);
            echo htmlspecialchars(implode('', $lastLines));
        } else {
            echo 'No hay logs todavía';
        }
        ?></pre>
    </div>

    <div class="card">
        <h2>🌐 Variables de Entorno (Seguras)</h2>
        <table>
            <?php
            $safe_vars = [
                'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
                'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
                'CACHE_DRIVER', 'SESSION_DRIVER', 'QUEUE_CONNECTION',
                'REDIS_HOST', 'REDIS_PORT',
                'SUNAT_ENVIRONMENT'
            ];
            foreach ($safe_vars as $var):
                $value = getenv($var);
            ?>
            <tr>
                <td><code><?= $var ?></code></td>
                <td><?= $value ? htmlspecialchars($value) : '<em>No configurado</em>' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h2>⚠️ Advertencia de Seguridad</h2>
        <p>Esta página muestra información sensible del sistema.</p>
        <p><strong>ELIMINA o PROTEGE este archivo (<code>public/info.php</code>) en producción.</strong></p>
    </div>
</body>
</html>
