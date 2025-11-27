<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$sql = "SELECT p.id, p.nombre_cliente, a.nombre_area AS area, p.area_id
        FROM pedidos p
        JOIN areas a ON p.area_id = a.id
        WHERE p.area_id IN (4, 5, 6)
        ORDER BY p.fecha_creacion DESC";

$result = $conn->query($sql);

$pedidos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
}

echo json_encode($pedidos);

$conn->close();
?>