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

if (!isset($input['receiver_id']) || !isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $input['receiver_id'];
    $message = trim($input['message']);
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        exit;
    }
    
    // Verificar que el receptor existe
    $check_sql = "SELECT id FROM usuarios WHERE id = ? AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $receiver_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario receptor no válido']);
        exit;
    }
    
    // Insertar el mensaje
    $sql = "INSERT INTO chat_messages (sender_id, receiver_id, message, timestamp, is_read) 
            VALUES (?, ?, ?, NOW(), 0)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado correctamente',
            'message_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar el mensaje'
        ]);
    }
    
    $stmt->close();
    $check_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>