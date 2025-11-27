<?php
session_start();

// Restringir a operadores (rol_id = 2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol_id'] != 2) {
    header("Location: login.php?error=unauthorized");
    exit();
}

require_once 'php/db_connection.php';
require_once 'php/get_sms_config.php';
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$sms_config = getSMSConfig();
$message = '';
$message_type = '';

// Validar credenciales guardadas
$client_id = trim($sms_config['onurix_client_id'] ?? '');
$api_key   = trim($sms_config['onurix_api_key'] ?? '');

if (empty($client_id) || empty($api_key)) {
    $message = 'Configura primero el Client ID y API Key de Onurix en Configuración SMS.';
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    $phone = trim($_POST['phone'] ?? '');
    $sms   = trim($_POST['sms'] ?? '');

    if (empty($phone) || empty($sms)) {
        $message = 'Número de teléfono y mensaje son obligatorios.';
        $message_type = 'error';
    } else {
        try {
            $client = new Client();

            // Usar webhook/proxy si está habilitado
            $useProxy = (($sms_config['sms_proxy_enabled'] ?? '0') === '1') && !empty($sms_config['sms_proxy_url']);
            if ($useProxy) {
                $payload = [
                    'phone' => $phone,
                    'sms' => $sms,
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

                $statusCode = $response->getStatusCode();
                $rawBody = $response->getBody()->getContents();
                $resBody = json_decode($rawBody, true);
                $success = is_array($resBody) ? ($resBody['success'] ?? false) : false;
                if ($statusCode === 200 && $success) {
                    $message = "✅ SMS enviado vía webhook.";
                    $message_type = 'success';
                } else {
                    $msg = is_array($resBody) ? ($resBody['message'] ?? 'Fallo al enviar SMS vía webhook') : 'Fallo al enviar SMS vía webhook';
                    $message = 'Error webhook: ' . $msg . ' (HTTP ' . $statusCode . ')';
                    $message_type = 'error';
                }
            } else {
                // Envío directo a Onurix (alineado con send_pedido_sms.php)
                $request_body = [
                    'client' => $client_id,
                    'key'    => $api_key,
                    'phone'  => $phone,
                    'sms'    => $sms,
                ];
                $response = $client->post('https://www.onurix.com/api/v1/sms/send', [
                    'form_params' => $request_body,
                    'timeout' => 30
                ]);

                $statusCode = $response->getStatusCode();
                $rawBody = $response->getBody()->getContents();
                $isHtmlResponse = stripos($rawBody, '<!DOCTYPE') !== false || stripos($rawBody, '<html') !== false;
                $body = $isHtmlResponse ? null : json_decode($rawBody, true);

                if ($statusCode === 200 && is_array($body) && isset($body['status']) && (int)$body['status'] === 1 && isset($body['data'])) {
                    $estado   = $body['data']['state'] ?? 'Enviando';
                    $telefono = $body['data']['phone'] ?? $phone;
                    $smsId    = $body['data']['id'] ?? '';
                    $message = "✅ SMS enviado. Estado: {$estado}. Teléfono: {$telefono}. ID: {$smsId}";
                    $message_type = 'success';
                } else {
                    if ($isHtmlResponse) {
                        $message = 'Error de conexión: La solicitud fue bloqueada por un firewall o proxy (HTTP ' . $statusCode . ')';
                    } elseif (is_array($body) && isset($body['error'], $body['msg'])) {
                        $message = 'Error de Onurix: ' . $body['msg'] . ' (Código: ' . $body['error'] . ')';
                    } else {
                        $message = 'Respuesta inesperada de Onurix: ' . ($body ? json_encode($body) : 'No JSON');
                    }
                    $message_type = 'error';
                }
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                // Capturar el cuerpo crudo antes de decodificar
                $rawBody = (string) $response->getBody();
                $isHtmlResponse = stripos($rawBody, '<!DOCTYPE') !== false || stripos($rawBody, '<html') !== false;
                $errorBody = json_decode($rawBody, true);
                $status = $response->getStatusCode();
                // Registrar en logs para diagnóstico
                error_log('Onurix TEST error - HTTP ' . $status . ' - Body: ' . substr($rawBody, 0, 500));

                if ($isHtmlResponse) {
                    $message = 'Error de conexión: La solicitud fue bloqueada por un firewall o proxy (HTTP ' . $status . ')';
                } elseif (is_array($errorBody) && (isset($errorBody['msg']) || isset($errorBody['message']))) {
                    $msg = $errorBody['msg'] ?? $errorBody['message'];
                    $code = $errorBody['error'] ?? ($errorBody['code'] ?? 'N/A');
                    $message = 'Error de Onurix: ' . $msg . ' (Código: ' . $code . ', HTTP ' . $status . ')';
                } else {
                    $message = 'Error HTTP de Onurix: ' . $status . '. Respuesta: ' . substr(trim($rawBody), 0, 200);
                }
            } else {
                $message = 'Error de conexión: ' . $e->getMessage();
            }
            $message_type = 'error';
        } catch (Exception $e) {
            $message = 'Error general: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas SMS (Onurix) - Glamcity</title>
    <link rel="stylesheet" href="css/config.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <style>
        .test-container { max-width: 900px; margin: 20px auto; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .counter { font-size: 0.9rem; color: #555; margin-top: 6px; }
        .actions { display: flex; gap: 10px; }
        .alert { margin: 10px 0; padding: 10px 12px; border-radius: 6px; }
        .alert-success { background: #e6f7ea; color: #1e6b2d; }
        .alert-error { background: #fdecea; color: #9f1c1c; }
        .badge { display: inline-block; background: #f5f5f5; border: 1px solid #eee; padding: 4px 8px; border-radius: 6px; margin-left: 6px; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-vial"></i>
                <h2>Pruebas de SMS (Onurix)</h2>
            </div>
            <div class="card-body">
                <p>Usa los datos guardados en <strong>Configuración SMS</strong> para enviar un SMS de prueba.</p>
                <p>Credenciales: Client ID <span class="badge"><?php echo htmlspecialchars($client_id ?: 'No configurado'); ?></span>, API Key <span class="badge"><?php echo $api_key ? 'Configurada' : 'No configurada'; ?></span></p>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="config-form">
                    <div class="form-group">
                        <label for="phone">Número de Teléfono</label>
                        <input type="text" id="phone" name="phone" placeholder="573151234567" required pattern="^[0-9,]+$" />
                        <small>Formato: 57 + número (sin espacios). Se permite lista separada por comas.</small>
                    </div>

                    <div class="form-group">
                        <label for="sms">Mensaje SMS</label>
                        <textarea id="sms" name="sms" rows="4" maxlength="1000" required placeholder="Escribe tu mensaje personalizado..."></textarea>
                        <div class="counter">
                            Caracteres: <span id="charCount">0</span> | Segmentos aproximados: <span id="segments">1</span>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn-success">
                            <i class="fas fa-paper-plane"></i> Enviar SMS de Prueba
                        </button>
                        <a href="config.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Configuración
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const smsInput = document.getElementById('sms');
        const charCountEl = document.getElementById('charCount');
        const segmentsEl = document.getElementById('segments');

        function updateCounter() {
            const text = smsInput.value || '';
            const count = text.length;
            charCountEl.textContent = count;
            // Estimación de segmentos: 160 chars por SMS estándar
            const segments = Math.max(1, Math.ceil(count / 160));
            segmentsEl.textContent = segments;
        }

        smsInput.addEventListener('input', updateCounter);
        updateCounter();
    </script>
</body>
</html>