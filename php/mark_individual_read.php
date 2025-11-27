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

if (!isset($input['sender_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID del remitente requerido']);
    exit;
}

try {
    $current_user_id = $_SESSION['user_id'];
    $sender_id = $input['sender_id'];
    
    // Marcar como leídos todos los mensajes del remitente hacia el usuario actual
    $sql = "UPDATE chat_messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sender_id, $current_user_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensajes marcados como leídos',
            'affected_rows' => $affected_rows
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al marcar mensajes como leídos'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>