<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['loggedin'])) {
    $pqrs_id = $_POST['pqrs_id'];
    $respuesta = $_POST['respuesta'];
    $estado = 'Cerrado'; // Se establece el estado directamente a 'Cerrado'
    
    // Verificar que user_id existe en la sesión, si no usar id
    $respondido_por_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];
    
    if (!$respondido_por_id) {
        echo json_encode(['error' => 'No se pudo identificar el usuario en la sesión']);
        exit;
    } 

    $stmt = $conn->prepare("UPDATE pqrs SET respuesta = ?, estado = ?, respondido_por_id = ?, fecha_respuesta = NOW() WHERE id = ?");
    $stmt->bind_param("ssii", $respuesta, $estado, $respondido_por_id, $pqrs_id);

    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode(['success' => 'PQRS actualizada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar la PQRS: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Acceso no autorizado']);
}

$conn->close();
?>