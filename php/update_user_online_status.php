<?php
/**
 * API para actualizar el estado online de usuarios en el chat
 * Endpoint: POST /php/update_user_online_status.php
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
    if (!isset($input['user_id']) || !isset($input['username'])) {
        throw new Exception('Datos de usuario requeridos');
    }
    
    $user_id = intval($input['user_id']);
    $username = trim($input['username']);
    $action = isset($input['action']) ? $input['action'] : 'ping'; // ping, join, leave
    
    // Sanitizar username
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    
    switch ($action) {
        case 'join':
        case 'ping':
            // Marcar usuario como online
            $stmt = $pdo->prepare("
                INSERT INTO chat_online_users (user_id, username, last_activity, is_online) 
                VALUES (?, ?, NOW(), TRUE)
                ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                last_activity = NOW(),
                is_online = TRUE
            ");
            $stmt->execute([$user_id, $username]);
            break;
            
        case 'leave':
            // Marcar usuario como offline
            $stmt = $pdo->prepare("
                UPDATE chat_online_users 
                SET is_online = FALSE, last_activity = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    // Limpiar usuarios offline automáticamente
    $stmt = $pdo->prepare("CALL CleanOfflineUsers()");
    $stmt->execute();
    
    // Obtener lista actualizada de usuarios online
    $stmt = $pdo->prepare("
        SELECT 
            user_id,
            username,
            last_activity,
            CASE 
                WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 2 MINUTE) THEN 'online'
                WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 'away'
                ELSE 'offline'
            END as status
        FROM chat_online_users 
        WHERE is_online = TRUE 
        AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY last_activity DESC
    ");
    $stmt->execute();
    $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar usuarios realmente online (activos en los últimos 2 minutos)
    $online_count = count(array_filter($online_users, function($user) {
        return $user['status'] === 'online';
    }));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'data' => [
            'action' => $action,
            'user_id' => $user_id,
            'username' => $username,
            'online_users' => $online_users,
            'online_count' => $online_count,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en update_user_online_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'details' => isLocalEnvironment() ? $e->getMessage() : 'Error interno del servidor'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
} catch (Throwable $e) {
    error_log("Error inesperado en update_user_online_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error inesperado del servidor'
    ]);
}
?>