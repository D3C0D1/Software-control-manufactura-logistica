<?php
header('Content-Type: application/json');
include 'db_connection.php';

$guia = isset($_GET['guia']) ? $_GET['guia'] : '';

if (empty($guia)) {
    echo json_encode(['error' => 'Número de guía no proporcionado.']);
    exit;
}

$sql = "SELECT p.nombre_pedido, e.nombre as estado, a.nombre as area, p.area_id
        FROM pedidos p
        JOIN estados e ON p.estado_id = e.id
        JOIN areas a ON p.area_id = a.id
        WHERE p.numero_guia = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $guia);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pedido = $result->fetch_assoc();
    echo json_encode($pedido);
} else {
    echo json_encode(['error' => 'Pedido no encontrado.']);
}

$stmt->close();
$conn->close();
?>