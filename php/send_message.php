<?php
session_start();
include 'db_connection.php';

// Habilitar el reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: Usuario no autenticado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['message']) || trim($data['message']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Error: El mensaje no puede estar vacío.']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$rol_id = $_SESSION['rol_id'];
$message = trim($data['message']);
$receiver_id = null;

if ($rol_id == 1) { // Si es Administrador
    if (empty($data['area_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Error: Como administrador, debe seleccionar un área de destino.']);
        exit;
    }
    $area_id = intval($data['area_id']);
    $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE area_id = ? LIMIT 1");
    $stmt_user->bind_param("i", $area_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($employee = $result_user->fetch_assoc()) {
        $receiver_id = $employee['id'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: No se encontró ningún empleado para el área seleccionada.']);
        exit;
    }
} else { // Si es Empleado
    $receiver_id = 1; // Los empleados siempre envían mensajes al administrador (ID 1)
}

if ($receiver_id) {
    $sql = "INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error: No se pudo determinar un destinatario válido.']);
}

$conn->close();
?>