<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No se proporcionó un ID de PQRS.']);
    exit;
}

$id = $_GET['id'];

$sql = "SELECT id, radicado, nombre_completo, email, telefono, usuario_id, tipo_solicitud, asunto, mensaje, respuesta, fecha_respuesta, respondido_por_id, archivo_adjunto, estado, fecha_creacion, fecha_actualizacion FROM pqrs WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pqrs = $result->fetch_assoc();
    
    // Si hay un respondido_por_id, obtener el nombre del usuario
    if ($pqrs['respondido_por_id']) {
        $user_sql = "SELECT nombre_completo FROM usuarios WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param('i', $pqrs['respondido_por_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $pqrs['respondido_por'] = $user_data['nombre_completo'];
        }
        $user_stmt->close();
    }
    
    echo json_encode($pqrs);
} else {
    echo json_encode(['error' => 'No se encontró ninguna PQRS con el ID proporcionado.']);
}

$stmt->close();
$conn->close();
?>