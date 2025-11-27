<?php
// Script puente (Hosting B) para enviar SMS a Onurix
// Ruta sugerida: https://impactusdigital.com.co/archivo.php

header('Content-Type: application/json');

// Configuración local del webhook
// Nota: Si no se define SMS_PROXY_TOKEN, la autenticación se desactiva (token opcional)
$ONURIX_CLIENT_ID = '7389';
$ONURIX_API_KEY = 'baf0076e7d995fc544c21cea4fdf898ce00612f268dc5f38c3565';

// Autenticación por Bearer Token (opcional)
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
if ($TOKEN_SECRETO !== '') {
    // Token configurado: exigir y validar
    if (stripos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token requerido']);
        exit;
    }
    $token = trim(substr($authHeader, 7));
    if (!hash_equals($TOKEN_SECRETO, $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
}

// Leer cuerpo JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cuerpo inválido']);
    exit;
}

$phone = isset($input['phone']) ? trim($input['phone']) : '';
$sms = isset($input['sms']) ? trim($input['sms']) : '';
$numero_guia = isset($input['numero_guia']) ? trim($input['numero_guia']) : null;
$pedido_id = isset($input['pedido_id']) ? $input['pedido_id'] : null;
$context = isset($input['context']) ? trim($input['context']) : null; // create|finalize|manual

// Validación básica
if ($phone === '' || $sms === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros requeridos: phone, sms']);
    exit;
}

// Credenciales: usar las provistas o las locales
$clientId = isset($input['client']) && $input['client'] !== '' ? $input['client'] : $ONURIX_CLIENT_ID;
$apiKey = isset($input['key']) && $input['key'] !== '' ? $input['key'] : $ONURIX_API_KEY;

if ($clientId === '' || $apiKey === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Credenciales de Onurix no disponibles']);
    exit;
}

// Enviar a Onurix vía cURL (para evitar dependencias)
$url = 'https://www.onurix.com/api/v1/sms/send';
$postFields = http_build_query([
    'client' => $clientId,
    'key' => $apiKey,
    'phone' => $phone,
    'sms' => $sms
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
]);

$raw = curl_exec($ch);
$err = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con Onurix: ' . $err,
        'status' => $status
    ]);
    exit;
}

$onurix = json_decode($raw, true);
$ok = ($status === 200) && is_array($onurix) && isset($onurix['data']['id']);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'SMS enviado' : (($onurix['msg'] ?? 'Fallo al enviar SMS') . ''),
    'status' => $status,
    'data' => [
        'onurix' => $onurix,
        'numero_guia' => $numero_guia,
        'pedido_id' => $pedido_id,
        'context' => $context
    ]
]);
?>