<?php
/**
 * API para enviar mensajes al chat grupal de Glamcity
 * Endpoint: POST /php/send_chat_message.php
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
require_once 'config.php';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    if (!isset($input['message']) || empty(trim($input['message']))) {
        throw new Exception('El mensaje no puede estar vacío');
    }
    
    if (!isset($input['user_id']) || !isset($input['username'])) {
        throw new Exception('Datos de usuario requeridos');
    }
    
    $message = trim($input['message']);
    $user_id = intval($input['user_id']);
    $username = trim($input['username']);
    $message_type = isset($input['message_type']) ? $input['message_type'] : 'text';
    
    // Validar longitud del mensaje
    $stmt = $pdo->prepare("SELECT config_value FROM chat_config WHERE config_key = 'max_message_length'");
    $stmt->execute();
    $max_length = intval($stmt->fetchColumn()) ?: 1000;
    
    if (strlen($message) > $max_length) {
        throw new Exception("El mensaje no puede exceder {$max_length} caracteres");
    }
    
    // Sanitizar mensaje
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    
    // Insertar mensaje en la base de datos
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (user_id, username, message, message_type) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$user_id, $username, $message, $message_type]);
    $message_id = $pdo->lastInsertId();
    
    // Actualizar estado de usuario online
    $stmt = $pdo->prepare("
        INSERT INTO chat_online_users (user_id, username, last_activity, is_online) 
        VALUES (?, ?, NOW(), TRUE)
        ON DUPLICATE KEY UPDATE 
        username = VALUES(username),
        last_activity = NOW(),
        is_online = TRUE
    ");
    
    $stmt->execute([$user_id, $username]);
    
    // Obtener el mensaje completo insertado
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            username,
            message,
            message_type,
            file_path,
            file_name,
            file_size,
            created_at,
            DATE_FORMAT(created_at, '%H:%i') as formatted_time
        FROM chat_messages 
        WHERE id = ?
    ");
    
    $stmt->execute([$message_id]);
    $sent_message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener conteo de usuarios online
    $stmt = $pdo->prepare("SELECT GetOnlineUsersCount() as online_count");
    $stmt->execute();
    $online_count = $stmt->fetchColumn();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'data' => [
            'message' => $sent_message,
            'online_users' => intval($online_count),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en send_chat_message.php: " . $e->getMessage());
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
    error_log("Error inesperado en send_chat_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error inesperado del servidor'
    ]);
}
?>