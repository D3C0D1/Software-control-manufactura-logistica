<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $current_user_id = $_SESSION['user_id'];
    
    // Obtener conteo de mensajes individuales no leídos
    $individual_sql = "SELECT COUNT(*) as count 
                       FROM chat_messages 
                       WHERE receiver_id = ? AND is_read = 0";
    $individual_stmt = $conn->prepare($individual_sql);
    $individual_stmt->bind_param("i", $current_user_id);
    $individual_stmt->execute();
    $individual_result = $individual_stmt->get_result();
    $individual_count = $individual_result->fetch_assoc()['count'];
    
    // Obtener información del usuario para verificar acceso a áreas
    $user_sql = "SELECT u.rol_id, a.nombre as user_area 
                 FROM usuarios u 
                 LEFT JOIN area_empleado a ON u.area_id = a.id 
                 WHERE u.id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $current_user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Determinar áreas a las que tiene acceso
    $accessible_areas = [];
    if ($user_data['rol_id'] == 1 || $user_data['rol_id'] == 3) { // admin o operador
        // Obtener todas las áreas
        $areas_sql = "SELECT DISTINCT nombre FROM area_empleado 
                      WHERE nombre IN ('mensajeria', 'recepcion', 'diseño', 'impresion', 'sublimado', 'confeccion', 'control_calidad')";
        $areas_result = $conn->query($areas_sql);
        while ($area = $areas_result->fetch_assoc()) {
            $accessible_areas[] = $area['nombre'];
        }
    } else {
        // Solo su área
        if ($user_data['user_area']) {
            $accessible_areas[] = $user_data['user_area'];
        }
    }
    
    // Obtener conteos de mensajes de grupo no leídos por área
    $group_counts = [];
    
    foreach ($accessible_areas as $area) {
        $group_sql = "SELECT COUNT(*) as count 
                      FROM group_chat_messages gm
                      LEFT JOIN group_message_reads gmr ON gm.id = gmr.message_id AND gmr.user_id = ?
                      WHERE gm.area = ? AND gmr.id IS NULL";
        $group_stmt = $conn->prepare($group_sql);
        $group_stmt->bind_param("is", $current_user_id, $area);
        $group_stmt->execute();
        $group_result = $group_stmt->get_result();
        $group_count = $group_result->fetch_assoc()['count'];
        
        $group_counts[$area] = $group_count;
        $group_stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'counts' => [
            'individual' => $individual_count,
            'groups' => $group_counts
        ]
    ]);
    
    $individual_stmt->close();
    $user_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}

$conn->close();
?>