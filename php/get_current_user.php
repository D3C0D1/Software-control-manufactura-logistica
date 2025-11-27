<?php
/**
 * API para obtener el usuario actual del sistema
 * Glamcity Chat System - Get Current User API
 * Modificado para soportar chat grupal
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, nombre_completo, usuario, foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'nombre_completo' => $user['nombre_completo'],
            'usuario' => $user['usuario'],
            'foto_perfil' => $user['foto_perfil']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>