<?php
header('Content-Type: application/json');
include 'db_connection.php';

$response = ['success' => false];

if (isset($_GET['radicado'])) {
    $radicado = $_GET['radicado'];

    $stmt = $conn->prepare("SELECT p.radicado, p.tipo_solicitud, p.mensaje, p.estado, p.fecha_creacion, p.respuesta, p.fecha_respuesta, u.usuario AS agente_nombre 
                             FROM pqrs p 
                             LEFT JOIN usuarios u ON p.respondido_por_id = u.id 
                             WHERE p.radicado = ?");
    $stmt->bind_param("s", $radicado);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pqrs = $result->fetch_assoc();
        $response = ['success' => true, 'data' => $pqrs];
    } else {
        $response['message'] = 'No se encontró ninguna solicitud con el número de radicado proporcionado.';
    }

    $stmt->close();
} else {
    $response['message'] = 'No se proporcionó un número de radicado.';
}

$conn->close();
echo json_encode($response);
?>