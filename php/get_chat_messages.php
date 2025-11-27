<?php
/**
 * API para obtener mensajes del chat grupal de Glamcity
 * Endpoint: GET /php/get_chat_messages.php
 * Parámetros opcionales:
 * - last_id: ID del último mensaje recibido (para obtener solo mensajes nuevos)
 * - limit: Número de mensajes a obtener (default: 50)
 * - offset: Offset para paginación
 */

require_once 'config.php';

// Configurar headers para JSON y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Obtener parámetros de la URL
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $username = isset($_GET['username']) ? trim($_GET['username']) : null;
    
    // Obtener límite por defecto de configuración
    if ($limit === null) {
        $stmt = $pdo->prepare("SELECT config_value FROM chat_config WHERE config_key = 'messages_per_load'");
        $stmt->execute();
        $limit = intval($stmt->fetchColumn()) ?: 50;
    }
    
    // Validar límite máximo
    $limit = min($limit, 100); // Máximo 100 mensajes por petición
    
    // Actualizar estado de usuario online si se proporcionan datos
    if ($user_id && $username) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_online_users (user_id, username, last_activity, is_online) 
            VALUES (?, ?, NOW(), TRUE)
            ON DUPLICATE KEY UPDATE 
            username = VALUES(username),
            last_activity = NOW(),
            is_online = TRUE
        ");
        $stmt->execute([$user_id, htmlspecialchars($username, ENT_QUOTES, 'UTF-8')]);
    }
    
    // Construir consulta SQL
    $sql = "
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
            DATE_FORMAT(created_at, '%H:%i') as formatted_time,
            DATE_FORMAT(created_at, '%Y-%m-%d') as formatted_date
        FROM chat_messages 
        WHERE is_deleted = FALSE
    ";
    
    $params = [];
    
    // Si se proporciona last_id, obtener solo mensajes más recientes
    if ($last_id > 0) {
        $sql .= " AND id > ?";
        $params[] = $last_id;
    }
    
    $sql .= " ORDER BY created_at DESC, id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Invertir orden para mostrar mensajes más antiguos primero
    if ($last_id === 0) {
        $messages = array_reverse($messages);
    }
    
    // Obtener conteo total de mensajes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE is_deleted = FALSE");
    $stmt->execute();
    $total_messages = intval($stmt->fetchColumn());
    
    // Obtener usuarios online
    $stmt = $pdo->prepare("
        SELECT 
            user_id,
            username,
            last_activity,
            CASE 
                WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
                WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'away'
                ELSE 'offline'
            END as status
        FROM chat_online_users 
        WHERE is_online = TRUE 
        AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY last_activity DESC
    ");
    $stmt->execute();
    $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener conteo de usuarios online
    $online_count = count(array_filter($online_users, function($user) {
        return $user['status'] === 'online';
    }));
    
    // Obtener configuración del chat
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM chat_config 
        WHERE config_key IN ('group_name', 'max_message_length', 'file_upload_enabled')
    ");
    $stmt->execute();
    $config_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($config_rows as $row) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    // Formatear mensajes para el frontend
    $formatted_messages = [];
    foreach ($messages as $message) {
        $formatted_messages[] = [
            'id' => intval($message['id']),
            'user_id' => intval($message['user_id']),
            'username' => $message['username'],
            'message' => $message['message'],
            'message_type' => $message['message_type'],
            'file_path' => $message['file_path'],
            'file_name' => $message['file_name'],
            'file_size' => $message['file_size'] ? intval($message['file_size']) : null,
            'created_at' => $message['created_at'],
            'formatted_time' => $message['formatted_time'],
            'formatted_date' => $message['formatted_date'],
            'is_today' => $message['formatted_date'] === date('Y-m-d')
        ];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $formatted_messages,
            'total_messages' => $total_messages,
            'online_users' => $online_users,
            'online_count' => $online_count,
            'config' => $config,
            'has_new_messages' => count($formatted_messages) > 0,
            'last_message_id' => count($formatted_messages) > 0 ? max(array_column($formatted_messages, 'id')) : 0,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get_chat_messages.php: " . $e->getMessage());
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
    error_log("Error inesperado en get_chat_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error inesperado del servidor'
    ]);
}
?>