<?php
/**
 * API para actualizar el estado de escritura de usuarios en el chat
 * Endpoint: POST /php/update_typing_status.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once 'config.php';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    if (!isset($input['user_id']) || !isset($input['username']) || !isset($input['is_typing'])) {
        throw new Exception('Datos requeridos: user_id, username, is_typing');
    }
    
    $user_id = intval($input['user_id']);
    $username = trim($input['username']);
    $is_typing = (bool)$input['is_typing'];
    
    // Sanitizar username
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    
    if ($is_typing) {
        // Usuario está escribiendo - insertar o actualizar registro
        $stmt = $pdo->prepare("
            INSERT INTO chat_typing_status (user_id, username, started_typing) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            username = VALUES(username),
            started_typing = NOW()
        ");
        
        $stmt->execute([$user_id, $username]);
        
        // Limpiar registros antiguos (más de 5 segundos)
        $stmt = $pdo->prepare("DELETE FROM chat_typing_status WHERE started_typing < DATE_SUB(NOW(), INTERVAL 5 SECOND)");
        $stmt->execute();
        
    } else {
        // Usuario dejó de escribir - eliminar registro
        $stmt = $pdo->prepare("DELETE FROM chat_typing_status WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
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
        'message' => 'Estado de escritura actualizado',
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