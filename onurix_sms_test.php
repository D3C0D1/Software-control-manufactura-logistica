<?php
// Tester independiente de Onurix SMS (fuera del programa)
// Coloca aquí tus credenciales reales de Onurix.

header('Content-Type: application/json');

// 1) CONFIGURA TUS CREDENCIALES AQUÍ
$CLIENT_ID = '7389';               // Reemplaza por tu Client ID real
$API_KEY   = 'PON_TU_API_KEY_AQUI'; // Reemplaza por tu API Key real

// 2) Lee entrada JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Cuerpo de solicitud inválido']);
    exit;
}

$phone = isset($input['phone']) ? trim($input['phone']) : '';
$sms   = isset($input['sms']) ? trim($input['sms']) : '';

if ($phone === '' || $sms === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan campos: phone y sms son requeridos']);
    exit;
}

// Validación básica del número (formato E.164 opcional)
$normalizedPhone = preg_replace('/[\s\-()]/', '', $phone);
if (!preg_match('/^\+?[0-9]{10,15}$/', $normalizedPhone)) {
    echo json_encode(['success' => false, 'message' => 'Número inválido. Usa formato internacional (ej: +573001234567).']);
    exit;
}

// 3) Preparar solicitud cURL a Onurix
$url = 'https://www.onurix.com/api/v1/sms/send';
$postFields = http_build_query([
    'client' => $CLIENT_ID,
    'key'    => $API_KEY,
    'phone'  => $normalizedPhone,
    'sms'    => $sms,
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($raw === false) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión cURL', 'error' => $curlErr]);
    exit;
}

$body = json_decode($raw, true);
if ($httpCode === 200 && is_array($body)) {
    // Onurix suele retornar un id dentro de data.id cuando el envío es exitoso
    $sentId = $body['data']['id'] ?? null;
    if ($sentId) {
        echo json_encode(['success' => true, 'message' => 'SMS enviado exitosamente', 'onurix' => $body]);
        exit;
    }
    // Si la respuesta no contiene id, mostrar el mensaje de Onurix si existe
    $msg = $body['msg'] ?? 'Respuesta de Onurix sin id de envío';
    echo json_encode(['success' => false, 'message' => $msg, 'onurix' => $body, 'status' => $httpCode]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'HTTP ' . $httpCode, 'raw' => $raw]);
exit;
?>