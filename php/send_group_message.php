<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Obtener datos JSON del cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['area']) || !isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $sender_id = $_SESSION['user_id'];
    $area = $input['area'];
    $message = trim($input['message']);
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        exit;
    }
    
    // Verificar que el usuario tenga acceso a esta área
    $access_sql = "SELECT u.rol_id, a.nombre as user_area 
                   FROM usuarios u 
                   LEFT JOIN area_empleado a ON u.area_id = a.id 
                   WHERE u.id = ?";
    $access_stmt = $conn->prepare($access_sql);
    $access_stmt->bind_param("i", $sender_id);
    $access_stmt->execute();
    $access_result = $access_stmt->get_result();
    $user_data = $access_result->fetch_assoc();
    
    // Admin y operador tienen acceso a todas las áreas
    $has_access = false;
    if ($user_data['rol_id'] == 1 || $user_data['rol_id'] == 3) { // admin o operador
        $has_access = true;
    } elseif ($user_data['user_area'] === $area) {
        $has_access = true;
    }
    
    if (!$has_access) {
        echo json_encode(['success' => false, 'message' => 'Sin acceso a esta área']);
        exit;
    }
    
    // Insertar el mensaje en el grupo
    $sql = "INSERT INTO group_chat_messages (sender_id, area, message, timestamp) 
            VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $sender_id, $area, $message);
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        
        // Marcar como leído para el remitente
        $read_sql = "INSERT INTO group_message_reads (message_id, user_id, read_at) 
                     VALUES (?, ?, NOW())";
        $read_stmt = $conn->prepare($read_sql);
        $read_stmt->bind_param("ii", $message_id, $sender_id);
        $read_stmt->execute();
        $read_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado correctamente',
            'message_id' => $message_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar el mensaje'
        ]);
    }
    
    $stmt->close();
    $access_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>