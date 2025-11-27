<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$sql = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.fecha_creacion, a.nombre_area AS area FROM pedidos p JOIN areas a ON p.area_id = a.id WHERE p.area_id = 10 ORDER BY p.fecha_creacion DESC";

$result = $conn->query($sql);

$pedidos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
}

echo json_encode($pedidos);

$conn->close();
?>