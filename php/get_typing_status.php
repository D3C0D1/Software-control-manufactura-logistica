<?php
/**
 * API para obtener el estado de escritura de usuarios en el chat
 * Endpoint: GET /php/get_typing_status.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener user_id del parámetro GET o POST
    $user_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
    }
    
    if (!$user_id) {
        throw new Exception('user_id requerido');
    }
    
    // Limpiar registros antiguos (más de 5 segundos)
    $stmt = $pdo->prepare("DELETE FROM chat_typing_status WHERE started_typing < DATE_SUB(NOW(), INTERVAL 5 SECOND)");
    $stmt->execute();
    
    // Obtener lista actual de usuarios escribiendo (excluyendo al usuario actual)
    $stmt = $pdo->prepare("
        SELECT username 
        FROM chat_typing_status 
        WHERE user_id != ? AND started_typing > DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ORDER BY started_typing ASC
    ");
    
    $stmt->execute([$user_id]);
    $typing_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Estado de escritura obtenido',
        'data' => [
            'typing_users' => $typing_users,
            'typing_count' => count($typing_users)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>