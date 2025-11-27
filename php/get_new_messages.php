<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { // Corregido de 'id' a 'user_id'
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit();
}

$current_user_id = $_SESSION['user_id']; // Corregido de 'id' a 'user_id'

// Obtener todos los mensajes donde el usuario actual es el remitente o el destinatario
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.recipient_id, m.message, m.timestamp, m.is_read, u.usuario as sender_name
    FROM new_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.sender_id = ? OR m.recipient_id = ?
    ORDER BY m.timestamp ASC");
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Opcional: Marcar los mensajes como leídos
// Aquí podrías agregar una lógica para actualizar el estado de los mensajes a 'leído'
// Por ejemplo, UPDATE new_messages SET is_read = 1 WHERE recipient_id = ? AND is_read = 0
$stmt_update = $conn->prepare("UPDATE new_messages SET is_read = 1 WHERE recipient_id = ? AND is_read = 0");
$stmt_update->bind_param("i", $current_user_id);
$stmt_update->execute();
$stmt_update->close();


echo json_encode(['status' => 'success', 'messages' => $messages, 'current_user_id' => $current_user_id]);

$conn->close();
?>