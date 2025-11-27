<?php
session_start();
require_once 'db_connection.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    // Calcular fechas límite según prioridad
    $fecha_actual = date('Y-m-d H:i:s');
    
    // Consulta para obtener pedidos vencidos según su prioridad
    $sql = "SELECT 
                p.id,
                p.numero_guia,
                p.nombre_cliente,
                p.prioridad,
                p.fecha_creacion,
                a.nombre as area_nombre,
                CASE 
                    WHEN p.prioridad = 'alta' THEN DATE_ADD(p.fecha_creacion, INTERVAL 1 DAY)
                    WHEN p.prioridad = 'normal' THEN DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY)
                    WHEN p.prioridad = 'baja' THEN DATE_ADD(p.fecha_creacion, INTERVAL 7 DAY)
                    ELSE DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY)
                END as fecha_limite,
                CASE 
                    WHEN p.prioridad = 'alta' THEN TIMESTAMPDIFF(HOUR, DATE_ADD(p.fecha_creacion, INTERVAL 1 DAY), NOW())
                    WHEN p.prioridad = 'normal' THEN TIMESTAMPDIFF(HOUR, DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY), NOW())
                    WHEN p.prioridad = 'baja' THEN TIMESTAMPDIFF(HOUR, DATE_ADD(p.fecha_creacion, INTERVAL 7 DAY), NOW())
                    ELSE TIMESTAMPDIFF(HOUR, DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY), NOW())
                END as horas_vencido
            FROM pedidos p
            LEFT JOIN areas a ON p.area_id = a.id
            WHERE p.estado_id != 4 -- No incluir pedidos finalizados
            AND (
                (p.prioridad = 'alta' AND DATE_ADD(p.fecha_creacion, INTERVAL 1 DAY) < NOW()) OR
                (p.prioridad = 'normal' AND DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY) < NOW()) OR
                (p.prioridad = 'baja' AND DATE_ADD(p.fecha_creacion, INTERVAL 7 DAY) < NOW()) OR
                (p.prioridad IS NULL AND DATE_ADD(p.fecha_creacion, INTERVAL 3 DAY) < NOW())
            )
            ORDER BY 
                CASE p.prioridad 
                    WHEN 'alta' THEN 1 
                    WHEN 'normal' THEN 2 
                    WHEN 'baja' THEN 3 
                    ELSE 2 
                END,
                p.fecha_creacion ASC";
    
    $result = $conn->query($sql);
    $pedidos_vencidos = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pedidos_vencidos[] = $row;
        }
    }
    
    // Contar pedidos por prioridad
    $contadores = [
        'alta' => 0,
        'normal' => 0,
        'baja' => 0,
        'total' => count($pedidos_vencidos)
    ];
    
    foreach ($pedidos_vencidos as $pedido) {
        $prioridad = $pedido['prioridad'] ?? 'normal';
        if (isset($contadores[$prioridad])) {
            $contadores[$prioridad]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'pedidos_vencidos' => $pedidos_vencidos,
        'contadores' => $contadores
    ]);
    
} catch (Exception $e) {
    error_log("Error al obtener pedidos vencidos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

$conn->close();
?>