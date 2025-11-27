<?php
/**
 * API para marcar el chat como visitado por el usuario
 * Endpoint: POST /php/mark_chat_visited.php
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
session_start();

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si existe la tabla
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'chat_user_last_visit'");
    $stmt->execute();
    $table_exists = $stmt->fetchColumn();
    
    if (!$table_exists) {
        // Crear tabla si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `chat_user_last_visit` (
                `user_id` int(11) NOT NULL,
                `last_visit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    
    // Actualizar la última visita del usuario
    $stmt = $pdo->prepare("
        INSERT INTO chat_user_last_visit (user_id, last_visit) 
        VALUES (?, NOW()) 
        ON DUPLICATE KEY UPDATE last_visit = NOW()
    ");
    $stmt->execute([$user_id]);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Chat marcado como visitado',
        'data' => [
            'user_id' => $user_id,
            'visit_time' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en mark_chat_visited.php: " . $e->getMessage());
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
    error_log("Error inesperado en mark_chat_visited.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error inesperado del servidor'
    ]);
}
?>