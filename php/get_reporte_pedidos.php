<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

/** @var mysqli $conn */
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode([
        'success' => false,
        'message' => 'Conexión a base de datos no disponible'
    ]);
    exit();
}

try {
    // Consulta para obtener todos los pedidos con información de área
    $sql = "SELECT 
                p.id,
                p.numero_guia,
                p.nombre_cliente,
                p.correo_cliente,
                p.numero_cliente,
                p.notas,
                p.prioridad,
                p.area_id,
                p.estado_id,
                p.fecha_creacion,
                p.fecha_actualizacion,
                a.nombre_area,
                CASE 
                    WHEN p.area_id = 10 THEN p.fecha_actualizacion
                    ELSE NULL
                END as fecha_finalizacion,
                CASE 
                    WHEN p.estado_id = 3 THEN (
                        SELECT u.nombre_completo 
                        FROM historial_pedidos h 
                        JOIN usuarios u ON h.usuario_entrada_id = u.id 
                        WHERE h.pedido_id = p.id AND h.area_id = 20 
                        ORDER BY h.fecha_entrada DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                END as eliminado_por
            FROM pedidos p
            LEFT JOIN areas a ON p.area_id = a.id
            ORDER BY p.fecha_creacion DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    // También obtener información del historial para calcular duraciones más precisas
    $sqlHistorial = "SELECT 
                        h.pedido_id,
                        MIN(h.fecha_entrada) as primera_entrada,
                        MAX(h.fecha_salida) as ultima_salida,
                        SUM(h.tiempo_en_area) as tiempo_total
                     FROM historial_pedidos h
                     GROUP BY h.pedido_id";
    
    $resultHistorial = $conn->query($sqlHistorial);
    
    $historialMap = [];
    if ($resultHistorial) {
        while ($row = $resultHistorial->fetch_assoc()) {
            $historialMap[$row['pedido_id']] = $row;
        }
    }
    
    // Enriquecer los pedidos con información del historial
    foreach ($pedidos as &$pedido) {
        if (isset($historialMap[$pedido['id']])) {
            $hist = $historialMap[$pedido['id']];
            $pedido['primera_entrada'] = $hist['primera_entrada'];
            $pedido['ultima_salida'] = $hist['ultima_salida'];
            $pedido['tiempo_total_segundos'] = $hist['tiempo_total'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total' => count($pedidos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener reportes: ' . $e->getMessage()
    ]);
}
?>