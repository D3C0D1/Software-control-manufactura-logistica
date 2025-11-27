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

if (!$user_area_id) {
    echo json_encode(['success' => false, 'error' => 'Usuario sin área asignada']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

try {
    if (isset($input['sms_id'])) {
        // Marcar un SMS específico como leído
        $sms_id = $input['sms_id'];
        
        // Verificar que el SMS fue RECIBIDO por el área del usuario
        $verify_sql = "SELECT id FROM sms_areas WHERE id = ? AND destination_area_id = ? AND is_active = 1";
        $verify_stmt = $pdo->prepare($verify_sql);
        $verify_stmt->execute([$sms_id, $user_area_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'SMS no encontrado o no autorizado']);
            exit;
        }
        
        // Insertar o actualizar el estado de lectura
        $sql = "INSERT INTO sms_areas_read_status (sms_id, user_id, read_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE read_at = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sms_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'SMS marcado como leído']);
        
    } else {
        // Marcar todos los SMS RECIBIDOS del área como leídos
        $sql = "INSERT INTO sms_areas_read_status (sms_id, user_id, read_at)
                SELECT sa.id, ?, NOW()
                FROM sms_areas sa
                LEFT JOIN sms_areas_read_status srs ON sa.id = srs.sms_id AND srs.user_id = ?
                WHERE sa.destination_area_id = ? AND sa.is_active = 1 AND srs.id IS NULL";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_area_id]);
        
        echo json_encode(['success' => true, 'message' => 'Todos los SMS marcados como leídos']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>