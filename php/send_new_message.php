<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { // Corregido de 'id' a 'user_id'
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit();
}

$sender_id = $_SESSION['user_id']; // Corregido de 'id' a 'user_id'
$role_id = $_SESSION['rol_id']; // Corregido de 'role_id' a 'rol_id'

$data = json_decode(file_get_contents('php://input'), true);

$message = $data['message'] ?? '';
$area_id = $data['area_id'] ?? null;
$recipient_id = $data['recipient_id'] ?? null;

if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje no puede estar vacío']);
    exit();
}

if ($role_id == 1) { // Si es Administrador
    if (!empty($area_id)) {
        // Enviar a todos los usuarios de un área
        $stmt = $conn->prepare("SELECT id FROM users WHERE area_id = ?");
        $stmt->bind_param("i", $area_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $conn->begin_transaction();
        try {
            $stmt_insert = $conn->prepare("INSERT INTO new_messages (sender_id, recipient_id, message) VALUES (?, ?, ?)");
            foreach ($users as $user) {
                $recipient_user_id = $user['id'];
                // El admin no se envía mensaje a sí mismo si pertenece al área
                if ($recipient_user_id != $sender_id) {
                    $stmt_insert->bind_param("iis", $sender_id, $recipient_user_id, $message);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Mensajes enviados al área con éxito']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error al enviar mensajes al área: ' . $e->getMessage()]);
        }

    } elseif (!empty($recipient_id)) {
        // Enviar a un usuario específico
        $stmt = $conn->prepare("INSERT INTO new_messages (sender_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $recipient_id, $message);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado con éxito']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al enviar el mensaje']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un área o un destinatario']);
    }
} else { // Si es Empleado
    // Los empleados siempre envían al admin (user_id = 1)
    $admin_id = 1;
    $stmt = $conn->prepare("INSERT INTO new_messages (sender_id, recipient_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $admin_id, $message);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado al administrador']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al enviar el mensaje']);
    }
    $stmt->close();
}

$conn->close();
?>