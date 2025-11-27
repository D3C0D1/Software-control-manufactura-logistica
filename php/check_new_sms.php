<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_area_id = $_SESSION['area_id'] ?? null;
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));

// Si el usuario no tiene área asignada, no puede recibir SMS
if (!$user_area_id) {
    echo json_encode(['success' => true, 'new_sms' => []]);
    exit;
}

try {
    // Buscar SMS nuevos RECIBIDOS en el área del usuario que no haya leído
    $sql = "SELECT sa.id, sa.message, sa.created_at, u.nombre_completo as sender_name, COALESCE(ae_sender.nombre, 'Administración') as sender_area
            FROM sms_areas sa
            JOIN usuarios u ON sa.sender_id = u.id
            LEFT JOIN area_empleado ae_sender ON sa.sender_area_id = ae_sender.id
            LEFT JOIN sms_areas_read_status srs ON sa.id = srs.sms_id AND srs.user_id = ?
            WHERE sa.destination_area_id = ? 
            AND sa.created_at > ? 
            AND sa.is_active = 1
            AND srs.id IS NULL
            ORDER BY sa.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_area_id, $last_check]);
    $new_sms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar SMS no leídos totales RECIBIDOS en el área
    $count_sql = "SELECT COUNT(*) as unread_count
                  FROM sms_areas sa
                  LEFT JOIN sms_areas_read_status srs ON sa.id = srs.sms_id AND srs.user_id = ?
                  WHERE sa.destination_area_id = ? 
                  AND sa.is_active = 1
                  AND srs.id IS NULL";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$user_id, $user_area_id]);
    $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    echo json_encode([
        'success' => true,
        'new_sms' => $new_sms,
        'unread_count' => $unread_count,
        'last_check' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>