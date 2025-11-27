<?php
/**
 * API para obtener la hora del servidor
 * Endpoint: GET /php/get_server_time.php
 * Devuelve la hora actual del servidor en diferentes formatos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener la hora actual del servidor
    $current_time = time();
    $datetime = new DateTime();
    
    // Respuesta con diferentes formatos de tiempo
    echo json_encode([
        'success' => true,
        'data' => [
            'timestamp' => $current_time,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'time_only' => $datetime->format('H:i'),
            'date_only' => $datetime->format('Y-m-d'),
            'formatted_time' => $datetime->format('H:i'),
            'formatted_date' => $datetime->format('Y-m-d'),
            'is_today' => true,
            'timezone' => date_default_timezone_get()
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener la hora del servidor',
        'details' => $e->getMessage()
    ]);
}
?>