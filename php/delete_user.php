<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

// Permitir acceso a admin (1) y operador (2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['rol_id'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$response = ['success' => false, 'message' => 'ID de usuario no proporcionado.'];

if (isset($_POST['id'])) {
    $user_id_to_delete = intval($_POST['id']);
    $current_user_id = $_SESSION['user_id'];
    $current_user_rol_id = $_SESSION['rol_id'];

    // No permitir que un usuario se elimine a sí mismo
    if ($user_id_to_delete == $current_user_id) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.']);
        exit;
    }

    // Obtener el rol del usuario que se va a eliminar
    $stmt = $conn->prepare("SELECT rol_id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_delete);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El usuario que intentas eliminar no existe.']);
        exit;
    }
    $user_to_delete = $result->fetch_assoc();
    $user_to_delete_rol_id = $user_to_delete['rol_id'];
    $stmt->close();

    // Un operador no puede eliminar a un administrador
    if ($current_user_rol_id == 2 && $user_to_delete_rol_id == 1) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar a un administrador.']);
        exit;
    }

    // Solo el admin puede eliminar a otro admin
    if ($current_user_rol_id != 1 && $user_to_delete_rol_id == 1) {
        echo json_encode(['success' => false, 'message' => 'Solo un administrador puede eliminar a otro administrador.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_delete);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = ['success' => true, 'message' => 'Usuario eliminado correctamente.'];
        } else {
            $response = ['success' => false, 'message' => 'No se encontró el usuario o ya fue eliminado.'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Error al eliminar el usuario: ' . $stmt->error];
    }
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>