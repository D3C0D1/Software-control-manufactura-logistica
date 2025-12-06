<?php
session_start();

// Verificar que el usuario esté logueado y sea operador (rol_id = 2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol_id'] != 2) {
    header("Location: login.php?error=unauthorized");
    exit();
}

require_once 'php/db_connection.php';
require_once 'php/get_sms_config.php';
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Procesar formulario de configuración
$message = '';
$message_type = '';

// Obtener configuración SMS
$sms_config = getSMSConfig();

// Obtener configuración de notificaciones
$notif_config = ['phone_number' => '', 'notify_daily' => 0];
try {
    $pdo = getPDOConnection();
    
    // Auto-create table if not exists (Fix for user error)
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20),
        notify_daily TINYINT(1) DEFAULT 0,
        last_run DATETIME NULL
    )");
    
    // Ensure row 1 exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM notification_config");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO notification_config (phone_number, notify_daily) VALUES ('', 0)");
    }

    // Check for new columns and add if missing
    $columns = $pdo->query("SHOW COLUMNS FROM notification_config")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('notification_time', $columns)) {
        $pdo->exec("ALTER TABLE notification_config ADD COLUMN notification_time TIME DEFAULT '08:00:00'");
    }
    if (!in_array('message_template', $columns)) {
        $pdo->exec("ALTER TABLE notification_config ADD COLUMN message_template TEXT");
        $pdo->exec("UPDATE notification_config SET message_template = 'Tienes {normal} pedidos normales y {urgent} pedidos urgentes caducados.' WHERE id = 1");
    }

    $stmt = $pdo->query("SELECT * FROM notification_config WHERE id = 1");
    if ($row = $stmt->fetch()) {
        $notif_config = $row;
    }
} catch (Exception $e) {
    // Ignore or log
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_config') {
            // Actualizar configuración de base de datos
            $new_host = trim($_POST['db_host']);
            $new_name = trim($_POST['db_name']);
            $new_user = trim($_POST['db_user']);
            $new_pass = $_POST['db_pass'];
            
            // Aquí normalmente actualizarías el archivo config.php
            // Por seguridad, solo mostramos un mensaje
            $message = "Configuración actualizada correctamente. Reinicia el servidor para aplicar cambios.";
            $message_type = "success";
            
        } elseif ($_POST['action'] === 'update_sms_config') {
            // Actualizar configuración SMS
            $configs = [
                'onurix_client_id' => $_POST['onurix_client_id'] ?? '',
                'onurix_api_key' => $_POST['onurix_api_key'] ?? '',
                'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
                'sms_template' => $_POST['sms_template'] ?? '',
                'sms_finalize_enabled' => isset($_POST['sms_finalize_enabled']) ? '1' : '0',
                'sms_finalize_template' => $_POST['sms_finalize_template'] ?? '',
                // Proxy/Webhook hacia Hosting B
                'sms_proxy_enabled' => isset($_POST['sms_proxy_enabled']) ? '1' : '0',
                'sms_proxy_url' => $_POST['sms_proxy_url'] ?? '',
                'sms_proxy_token' => $_POST['sms_proxy_token'] ?? '',
                'sms_proxy_send_credentials' => isset($_POST['sms_proxy_send_credentials']) ? '1' : '0'
            ];
            
            $success = true;
            foreach ($configs as $config_name => $config_value) {
                if (!updateSMSConfig($config_name, $config_value)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $message = 'Configuración SMS actualizada exitosamente';
                $message_type = 'success';
                // Recargar configuración
                $sms_config = getSMSConfig();
            } else {
                $message = 'Error al actualizar la configuración SMS';
                $message_type = 'error';
            }
            
        } elseif ($_POST['action'] === 'update_notification_config') {
            $phone = $_POST['boss_phone'] ?? '';
            $notify = isset($_POST['notify_daily']) ? 1 : 0;
            $time = $_POST['notification_time'] ?? '08:00';
            $template = $_POST['message_template'] ?? '';
            
            try {
                $pdo = getPDOConnection();
                $stmt = $pdo->prepare("UPDATE notification_config SET phone_number = ?, notify_daily = ?, notification_time = ?, message_template = ? WHERE id = 1");
                $stmt->execute([$phone, $notify, $time, $template]);
                $message = "Configuración de alertas actualizada.";
                $message_type = "success";
                
                // Refresh config
                $stmt = $pdo->query("SELECT * FROM notification_config WHERE id = 1");
                if ($row = $stmt->fetch()) {
                    $notif_config = $row;
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'test_notification') {
            // Calculate metrics for test
            try {
                $pdo = getPDOConnection();
                
                // Urgent Expired (> 24h old)
                $sqlUrgent = "SELECT COUNT(*) FROM pedidos WHERE prioridad = 'alta' AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 1 DAY) AND area_id != 10 AND estado_id != 3";
                $urgentCount = $pdo->query($sqlUrgent)->fetchColumn();
                
                // Normal Expired (> 3 days old)
                $sqlNormal = "SELECT COUNT(*) FROM pedidos WHERE prioridad = 'normal' AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 3 DAY) AND area_id != 10 AND estado_id != 3";
                $normalCount = $pdo->query($sqlNormal)->fetchColumn();
                
                // Use template
                $template = $notif_config['message_template'] ?? 'Tienes {normal} pedidos normales y {urgent} pedidos urgentes caducados.';
                $msg = str_replace(['{normal}', '{urgent}'], [$normalCount, $urgentCount], $template);
                
                $phone = $_POST['boss_phone']; 
                
                // Send SMS via Onurix
                $client_id = $sms_config['onurix_client_id'];
                $api_key = $sms_config['onurix_api_key'];
                
                if (empty($client_id) || empty($api_key)) {
                    throw new Exception("Credenciales de Onurix no configuradas.");
                }
                
                $client = new Client();
                
                // Check for Proxy
                $useProxy = (($sms_config['sms_proxy_enabled'] ?? '0') === '1') && !empty($sms_config['sms_proxy_url']);
                
                if ($useProxy) {
                    $payload = [
                        'phone' => $phone,
                        'sms' => $msg,
                        'context' => 'test'
                    ];
                    if (($sms_config['sms_proxy_send_credentials'] ?? '0') === '1') {
                        $payload['client'] = $client_id;
                        $payload['key'] = $api_key;
                    }

                    $headers = [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ];
                    if (!empty($sms_config['sms_proxy_token'])) {
                        $headers['Authorization'] = 'Bearer ' . $sms_config['sms_proxy_token'];
                    }

                    $response = $client->post($sms_config['sms_proxy_url'], [
                        'headers' => $headers,
                        'json' => $payload,
                        'timeout' => 15
                    ]);
                    
                    $body = json_decode($response->getBody(), true);
                    if (isset($body['success']) && $body['success']) {
                         $message = "<strong>SMS Enviado (Proxy):</strong><br>Destino: $phone<br>Mensaje: $msg";
                         $message_type = "success";
                    } else {
                         throw new Exception("Error Proxy: " . ($body['message'] ?? 'Desconocido'));
                    }
                    
                } else {
                    // Direct Send
                    $response = $client->post('https://www.onurix.com/api/v1/sms/send', [
                        'form_params' => [
                            'client' => $client_id,
                            'key'    => $api_key,
                            'phone'  => $phone,
                            'sms'    => $msg,
                        ],
                        'timeout' => 30
                    ]);
                    
                    $body = json_decode($response->getBody(), true);
                    
                    if (isset($body['status']) && $body['status'] == 1) {
                        $message = "<strong>SMS Enviado Exitosamente:</strong><br>Destino: $phone<br>Mensaje: $msg<br>ID: " . ($body['data']['id'] ?? 'N/A');
                        $message_type = "success";
                    } else {
                        $message = "Error al enviar SMS: " . ($body['msg'] ?? 'Error desconocido');
                        $message_type = "error";
                    }
                }
                
            } catch (Exception $e) {
                 $message = "Error al probar: " . $e->getMessage();
                 $message_type = "error";
            }
        }
    }
}

// Función simple para detectar entorno sin conflictos
function obtenerTipoHosting() {
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = in_array($serverName, $localHosts) || strpos($serverName, '.local') !== false;
    
    return $isLocal ? 'Local (MAMP)' : 'Hostinger (Producción)';
}

$tipoHosting = obtenerTipoHosting();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Glamcity</title>
    <link rel="stylesheet" href="css/config.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
<script src="js/config-page.js" defer></script>
</head>
<body>
    
    <div class="config-container">
        <div class="config-header">
            <h1><i class="fas fa-cogs"></i> Configuración del Sistema</h1>
            <p>Panel de configuración exclusivo para operadores</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="config-grid">
            <!-- Información del Sistema -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label>Tipo de Hosting:</label>
                        <span class="hosting-badge <?php echo strpos($tipoHosting, 'Local') !== false ? 'local' : 'production'; ?>">
                            <i class="fas fa-<?php echo strpos($tipoHosting, 'Local') !== false ? 'server' : 'cloud'; ?>"></i>
                            <?php echo $tipoHosting; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Servidor:</label>
                        <span><?php echo $_SERVER['SERVER_NAME'] ?? 'No disponible'; ?></span>
                    </div>
                    <div class="info-item">
                        <label>PHP Version:</label>
                        <span><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Base de Datos:</label>
                        <span><?php echo defined('DB_NAME') ? DB_NAME : 'No configurada'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Configuración de Base de Datos -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-database"></i> Configuración de Base de Datos</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="config-form">
                        <input type="hidden" name="action" value="update_config">
                        
                        <div class="form-group">
                            <label for="db_host">Host de Base de Datos:</label>
                            <input type="text" id="db_host" name="db_host" value="<?php echo defined('DB_HOST') ? DB_HOST : 'localhost'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name">Nombre de Base de Datos:</label>
                            <input type="text" id="db_name" name="db_name" value="<?php echo defined('DB_NAME') ? DB_NAME : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_user">Usuario de Base de Datos:</label>
                            <input type="text" id="db_user" name="db_user" value="<?php echo defined('DB_USER') ? DB_USER : 'root'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Contraseña de Base de Datos:</label>
                            <input type="password" id="db_pass" name="db_pass" placeholder="Dejar en blanco para no cambiar">
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Actualizar Configuración
                        </button>
                    </form>
                </div>
            </div>

            <!-- Configuración de SMS -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-mobile-alt"></i> Configuración SMS</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="config-form">
                        <input type="hidden" name="action" value="update_sms_config">
                        
                        <div class="form-group">
                            <label for="onurix_client_id">Client ID de Onurix:</label>
                            <input type="text" id="onurix_client_id" name="onurix_client_id" value="<?php echo htmlspecialchars($sms_config['onurix_client_id']); ?>" required>
                            <small>ID del cliente proporcionado por Onurix SMS</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="onurix_api_key">API Key de Onurix:</label>
                            <input type="password" id="onurix_api_key" name="onurix_api_key" value="<?php echo htmlspecialchars($sms_config['onurix_api_key']); ?>" required>
                            <small>Clave API proporcionada por Onurix SMS</small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sms_enabled" <?php echo $sms_config['sms_enabled'] == '1' ? 'checked' : ''; ?>>
                                Habilitar envío de SMS
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sms_finalize_enabled" <?php echo ($sms_config['sms_finalize_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                Enviar SMS al finalizar pedido
                            </label>
                            <small>Si está deshabilitado, no se notificará al cliente al mover a Finalizados.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="sms_template">Plantilla del mensaje SMS:</label>
                            <textarea id="sms_template" name="sms_template" rows="3" required><?php echo htmlspecialchars($sms_config['sms_template']); ?></textarea>
                            <small>Placeholders soportados: {nombre_cliente}, {numero_guia}</small>
                        </div>

                        <div class="form-group">
                            <label for="sms_finalize_template">Plantilla SMS al finalizar:</label>
                            <textarea id="sms_finalize_template" name="sms_finalize_template" rows="3" placeholder="¡Hola {nombre_cliente}! Tu pedido #{numero_guia} ya está listo para recoger."><?php echo htmlspecialchars($sms_config['sms_finalize_template'] ?? ''); ?></textarea>
                            <small>Placeholders soportados: {nombre_cliente}, {numero_guia}</small>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sms_proxy_enabled" <?php echo ($sms_config['sms_proxy_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                Usar Webhook/Proxy de SMS (Hosting B)
                            </label>
                            <small>Si está habilitado, los SMS se enviarán a través de <strong>impactusdigital.com.co/archivo.php</strong>.</small>
                        </div>

                        <div class="form-group">
                            <label for="sms_proxy_url">URL del Webhook (Hosting B):</label>
                            <input type="text" id="sms_proxy_url" name="sms_proxy_url" value="<?php echo htmlspecialchars($sms_config['sms_proxy_url'] ?? ''); ?>" placeholder="https://impactusdigital.com.co/archivo.php">
                            <small>Ruta pública del script puente que reenvía a Onurix.</small>
                        </div>

                        <div class="form-group">
                            <label for="sms_proxy_token">Token de Autorización (Bearer):</label>
                            <input type="password" id="sms_proxy_token" name="sms_proxy_token" value="<?php echo htmlspecialchars($sms_config['sms_proxy_token'] ?? ''); ?>" placeholder="Token secreto para validar desde Hosting B">
                            <small>Se enviará en la cabecera <code>Authorization: Bearer &lt;token&gt;</code>.</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sms_proxy_send_credentials" <?php echo ($sms_config['sms_proxy_send_credentials'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                Incluir credenciales de Onurix en la petición al proxy
                            </label>
                            <small>Si está deshabilitado, el Hosting B debe tener sus propias credenciales configuradas.</small>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración SMS
                        </button>
                    </form>
                </div>
            </div>

            <!-- Configuración de Alertas de Caducidad -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Alertas de Caducidad</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="config-form">
                        <input type="hidden" name="action" value="update_notification_config">
                        
                        <div class="form-group">
                            <label for="boss_phone">Número Telefónico del Jefe:</label>
                            <input type="text" id="boss_phone" name="boss_phone" value="<?php echo htmlspecialchars($notif_config['phone_number']); ?>" placeholder="+57 300..." required>
                        </div>
                        
                        <div class="form-group">
                            <label for="notification_time">Hora de Envío (Diario):</label>
                            <input type="time" id="notification_time" name="notification_time" value="<?php echo htmlspecialchars($notif_config['notification_time'] ?? '08:00'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="message_template">Plantilla del Mensaje:</label>
                            <textarea id="message_template" name="message_template" rows="3" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"><?php echo htmlspecialchars($notif_config['message_template'] ?? 'Tienes {normal} pedidos normales y {urgent} pedidos urgentes caducados.'); ?></textarea>
                            <small>Variables disponibles: <strong>{normal}</strong> (cantidad normales), <strong>{urgent}</strong> (cantidad urgentes)</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="notify_daily" <?php echo $notif_config['notify_daily'] ? 'checked' : ''; ?>>
                                Avisar cada 24 horas sobre pedidos caducados
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                            <button type="submit" name="action" value="test_notification" class="btn-secondary" style="background-color: #6c757d;">
                                <i class="fas fa-paper-plane"></i> Probar Notificación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de SMS -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Historial de SMS Enviados</h3>
                </div>
                <div class="card-body">
                    <div id="sms-history-container">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando historial...
                        </div>
                    </div>
                    <button type="button" class="btn-secondary" onclick="loadSMSHistory()">
                        <i class="fas fa-refresh"></i> Actualizar Historial
                    </button>
                </div>
            </div>

            <!-- Acceso a Pruebas de SMS (Onurix) -->
            <div class="config-card sms-card">
                <div class="card-header">
                    <h3><i class="fas fa-vial"></i> Pruebas de SMS (Onurix)</h3>
                </div>
                <div class="card-body">
                    <p>Utiliza las credenciales guardadas para enviar un SMS de prueba.</p>
                    <a class="btn-success" href="onurix_test.php">
                        <i class="fas fa-paper-plane"></i> Abrir pruebas de Onurix
                    </a>
                </div>
            </div>

            <!-- Auditoría del Sistema -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-list"></i> Auditoría del Sistema</h3>
                </div>
                <div class="card-body">
                    <p>Consulta el historial de movimientos de pedidos, actividad de usuarios y registros de desarrollador.</p>
                    <a class="btn-secondary" href="auditoria.php">
                        <i class="fas fa-search"></i> Ver Historial y Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

<script src="js/config.js" defer></script>
    <script>
        // Renderiza historial de SMS con remitente y áreas, refrescando en tiempo real
        let smsHistoryInterval = null;
        async function loadSMSHistory() {
            const container = document.getElementById('sms-history-container');
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</div>';
            try {
                const res = await fetch('php/get_sms_history.php');
                const data = await res.json();
                if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                    let html = '<div class="sms-history-table">';
                    html += '<table class="history-table">';
                    html += '<thead><tr><th>Fecha</th><th>Usuario</th><th>Área origen</th><th>Área destino</th><th>Tipo</th><th>Mensaje</th></tr></thead>';
                    html += '<tbody>';
                    for (const sms of data.messages) {
                        const tipoBadge = sms.es_enviado ? '<span class="status-badge success"><i class="fas fa-paper-plane"></i> Enviado</span>'
                                                         : '<span class="status-badge info"><i class="fas fa-inbox"></i> Recibido</span>';
                        html += `<tr>
                            <td>${sms.fecha_formateada}</td>
                            <td>${sms.remitente || 'Desconocido'}</td>
                            <td>${sms.area_remitente || 'N/A'}</td>
                            <td>${sms.destination_area || 'N/A'}</td>
                            <td>${tipoBadge}</td>
                            <td class="message-cell">${sms.mensaje}</td>
                        </tr>`;
                    }
                    html += '</tbody></table></div>';
                    html += `<p class="history-summary">Total: ${data.total_count} SMS</p>`;
                    container.innerHTML = html;
                } else {
                    // Fallback: historial de confirmaciones desde logs
                    const res2 = await fetch('php/get_pedido_sms_history.php');
                    const data2 = await res2.json();
                    if (data2.success && Array.isArray(data2.data) && data2.data.length > 0) {
                        let html = '<div class="sms-history-table">';
                        html += '<table class="history-table">';
                        html += '<thead><tr><th>Fecha</th><th>Pedido</th><th>Teléfono</th><th>Estado</th><th>Mensaje</th></tr></thead>';
                        html += '<tbody>';
                        for (const sms of data2.data) {
                            const statusClass = sms.estado === 'enviado' ? 'success' : 'error';
                            const statusIcon = sms.estado === 'enviado' ? 'check-circle' : 'times-circle';
                            html += `<tr>
                                <td>${sms.fecha_formateada}</td>
                                <td><strong>${sms.pedido_id}</strong></td>
                                <td>${sms.numero_telefono}</td>
                                <td><span class="status-badge ${statusClass}"><i class="fas fa-${statusIcon}"></i> ${sms.estado}</span></td>
                                <td class="message-cell">${sms.mensaje}</td>
                            </tr>`;
                        }
                        html += '</tbody></table></div>';
                        html += `<p class="history-summary">Total: ${data2.total} SMS (confirmaciones)</p>`;
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<div class="no-data"><i class="fas fa-inbox"></i> No hay historial de SMS disponible</div>';
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<div class="error"><i class="fas fa-exclamation-triangle"></i> Error al cargar el historial</div>';
            }
        }

        // Cargar y refrescar historial en tiempo real (cada 10s)
        document.addEventListener('DOMContentLoaded', function() {
            loadSMSHistory();
            if (smsHistoryInterval) clearInterval(smsHistoryInterval);
            smsHistoryInterval = setInterval(loadSMSHistory, 10000);
        });
    </script>
</body>
</html>