<?php
/**
 * Trigger para notificar cambios en el sistema de notificaciones
 * Se llama cuando se envía un nuevo mensaje para actualizar las notificaciones instantáneamente
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
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Leer datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'message_sent';
    
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tabla para triggers de notificaciones si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notification_triggers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `trigger_type` varchar(50) NOT NULL,
            `user_id` int(11) NOT NULL,
            `data` json DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // Insertar trigger de notificación
    $stmt = $pdo->prepare("
        INSERT INTO notification_triggers (trigger_type, user_id, data) 
        VALUES (?, ?, ?)
    ");
    
    $trigger_data = [
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id
    ];
    
    $stmt->execute([$action, $user_id, json_encode($trigger_data)]);
    
    // Limpiar triggers antiguos (más de 1 hora)
    $pdo->exec("
        DELETE FROM notification_triggers 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Trigger de notificación enviado',
        'data' => [
            'trigger_id' => $pdo->lastInsertId(),
            'action' => $action,
            'user_id' => $user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en trigger_notification.php: " . $e->getMessage());
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
    error_log("Error inesperado en trigger_notification.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error inesperado del servidor'
    ]);
}
?>