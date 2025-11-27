<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$sql = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.notas, p.prioridad, a.nombre_area AS area, p.area_id,
        CASE 
            WHEN p.area_id = 3 THEN (
                SELECT u.nombre_completo 
                FROM historial_pedidos hp 
                JOIN usuarios u ON hp.usuario_entrada_id = u.id 
                WHERE hp.pedido_id = p.id AND hp.area_id = 3 
                ORDER BY hp.fecha_entrada DESC 
                LIMIT 1
            )
            ELSE NULL
        END AS usuario_proceso_diseno,
        CASE WHEN md.pedido_id IS NOT NULL THEN 1 ELSE 0 END as tiene_devoluciones
        FROM pedidos p
        JOIN areas a ON p.area_id = a.id
        LEFT JOIN (SELECT DISTINCT pedido_id FROM mensajes_devolucion) md ON p.id = md.pedido_id
        WHERE p.area_id IN (1, 2, 3)
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