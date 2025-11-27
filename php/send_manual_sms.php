<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once 'db_connection.php';
require_once 'get_sms_config.php';
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$sms   = trim($_POST['sms'] ?? '');

if ($phone === '' || $sms === '') {
    echo json_encode(['success' => false, 'message' => 'Teléfono y mensaje son obligatorios']);
    exit;
}

if (!preg_match('/^[0-9]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'El teléfono debe contener solo dígitos']);
    exit;
}

if (strlen($sms) > 160) {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede superar 160 caracteres']);
    exit;
}

$config = getSMSConfig();
if (($config['sms_enabled'] ?? '1') !== '1') {
    echo json_encode(['success' => false, 'message' => 'El envío de SMS está deshabilitado']);
    exit;
}

// Determinar modo: Proxy/Webhook vs envío directo
$useProxy = (($config['sms_proxy_enabled'] ?? '0') === '1') && !empty($config['sms_proxy_url']);
$requireCreds = !$useProxy || (($config['sms_proxy_send_credentials'] ?? '0') === '1');

$clientId = trim($config['onurix_client_id'] ?? '');
$apiKey   = trim($config['onurix_api_key'] ?? '');

if ($requireCreds && ($clientId === '' || $apiKey === '')) {
    echo json_encode(['success' => false, 'message' => 'Credenciales de Onurix no configuradas']);
    exit;
}

try {
    $client = new Client();

    if ($useProxy) {
        // Envío vía Proxy/Webhook (Hosting B)
        $payload = [
            'phone' => $phone,
            'sms' => $sms,
            'context' => 'manual'
        ];
        if (($config['sms_proxy_send_credentials'] ?? '0') === '1') {
            $payload['client'] = $clientId;
            $payload['key'] = $apiKey;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        if (!empty($config['sms_proxy_token'])) {
            $headers['Authorization'] = 'Bearer ' . $config['sms_proxy_token'];
        }

        $response = $client->post($config['sms_proxy_url'], [
            'headers' => $headers,
            'json' => $payload,
            'timeout' => 20
        ]);

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getBody()->getContents();
        $body = json_decode($rawBody, true);

        if ($statusCode === 200 && is_array($body) && isset($body['success']) && $body['success'] === true) {
            echo json_encode([
                'success' => true,
                'message' => $body['message'] ?? 'SMS enviado exitosamente',
                'data'    => $body['data'] ?? null
            ]);
            exit;
        }

        // Error desde Proxy
        $msg = 'Error del proxy: ' . ($body['message'] ?? 'Respuesta inesperada');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    } else {
        // Envío directo a Onurix
        $response = $client->request('POST', 'https://www.onurix.com/api/v1/sms/send', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client' => $clientId,
                'key'    => $apiKey,
                'phone'  => $phone,
                'sms'    => $sms,
            ],
            'timeout' => 15,
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);

        if ($statusCode === 200 && isset($body['status']) && (int)$body['status'] === 1 && isset($body['data'])) {
            echo json_encode([
                'success' => true,
                'message' => 'SMS enviado exitosamente',
                'data'    => $body['data']
            ]);
            exit;
        }

        if (isset($body['error'], $body['msg'])) {
            echo json_encode(['success' => false, 'message' => 'Error de Onurix: ' . $body['msg']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Respuesta inesperada de Onurix']);
        }
    }
} catch (RequestException $e) {
    $resp = $e->getResponse();
    if ($resp) {
        // Capturar el cuerpo crudo antes de intentar decodificar
        $rawBody = (string) $resp->getBody();
        // Detectar si la respuesta es HTML (típico de firewalls/proxies)
        $isHtmlResponse = stripos($rawBody, '<!DOCTYPE') !== false || stripos($rawBody, '<html') !== false;
        $errBody = json_decode($rawBody, true);
        $status = $resp->getStatusCode();
        // Registrar en logs para diagnóstico
        error_log('Onurix SMS error - HTTP ' . $status . ' - Body: ' . substr($rawBody, 0, 500));

        if ($isHtmlResponse) {
            // Es una respuesta HTML, probablemente de un firewall o proxy
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión: La solicitud fue bloqueada por un firewall o proxy (HTTP ' . $status . ')',
                'status' => $status,
                'details' => 'Contacte al administrador del hosting para permitir conexiones a www.onurix.com'
            ]);
        } elseif (is_array($errBody) && (isset($errBody['msg']) || isset($errBody['message']))) {
            $msg = $errBody['msg'] ?? $errBody['message'];
            $code = $errBody['error'] ?? ($errBody['code'] ?? 'N/A');
            echo json_encode([
                'success' => false,
                'message' => 'Error de Onurix: ' . $msg . ' (Código: ' . $code . ')',
                'status' => $status
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error HTTP: ' . $status . '. Respuesta: ' . substr(trim($rawBody), 0, 200),
                'status' => $status
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error general: ' . $e->getMessage()]);
}