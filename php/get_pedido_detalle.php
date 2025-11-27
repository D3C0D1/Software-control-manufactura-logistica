<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado.']);
    exit;
}

$pedido_id = intval($_GET['id']);

try {
    // Obtener detalles completos del pedido
    $sql_pedido = "
        SELECT 
            p.id,
            p.numero_guia,
            p.nombre_cliente,
            p.correo_cliente,
            p.numero_cliente,
            p.notas,
            p.prioridad,
            p.archivo_adjunto,
            p.area_id,
            p.estado_id,
            p.creado_por,
            p.fecha_creacion,
            p.fecha_actualizacion,
            a.nombre_area as area_actual,
            e.nombre_estado as estado_actual,
            u.nombre_completo as creado_por_nombre,
            CASE WHEN md.pedido_id IS NOT NULL THEN 1 ELSE 0 END as tiene_devoluciones
        FROM pedidos p
        LEFT JOIN areas a ON p.area_id = a.id
        LEFT JOIN estados_pedido e ON p.estado_id = e.id
        LEFT JOIN usuarios u ON p.creado_por = u.id
        LEFT JOIN (SELECT DISTINCT pedido_id FROM mensajes_devolucion) md ON p.id = md.pedido_id
        WHERE p.id = ?
    ";
    
    $stmt = $conn->prepare($sql_pedido);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($pedido = $result->fetch_assoc()) {
        // Obtener historial de áreas recorridas
        $sql_historial = "
            SELECT 
                h.id,
                h.area_id,
                h.fecha_entrada,
                h.fecha_salida,
                h.tiempo_en_area,
                a.nombre_area,
                u_entrada.nombre_completo as usuario_entrada,
                u_salida.nombre_completo as usuario_salida
            FROM historial_pedidos h
            LEFT JOIN areas a ON h.area_id = a.id
            LEFT JOIN usuarios u_entrada ON h.usuario_entrada_id = u_entrada.id
            LEFT JOIN usuarios u_salida ON h.usuario_salida_id = u_salida.id
            WHERE h.pedido_id = ?
            ORDER BY h.fecha_entrada ASC
        ";
        
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("i", $pedido_id);
        $stmt_historial->execute();
        $result_historial = $stmt_historial->get_result();
        
        $historial = [];
        while ($row = $result_historial->fetch_assoc()) {
            // Formatear fechas
            $row['fecha_entrada_formateada'] = date('d/m/Y H:i:s', strtotime($row['fecha_entrada']));
            $row['fecha_salida_formateada'] = $row['fecha_salida'] ? date('d/m/Y H:i:s', strtotime($row['fecha_salida'])) : 'En proceso';
            
            // Formatear tiempo en área
            if ($row['tiempo_en_area']) {
                $horas = floor($row['tiempo_en_area'] / 3600);
                $minutos = floor(($row['tiempo_en_area'] % 3600) / 60);
                $segundos = $row['tiempo_en_area'] % 60;
                $row['tiempo_formateado'] = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
            } else {
                $row['tiempo_formateado'] = 'En proceso';
            }
            
            $historial[] = $row;
        }
        
        $pedido['historial_areas'] = $historial;
        
        // Formatear fechas del pedido
        $pedido['fecha_creacion_formateada'] = date('d/m/Y H:i:s', strtotime($pedido['fecha_creacion']));
        $pedido['fecha_actualizacion_formateada'] = date('d/m/Y H:i:s', strtotime($pedido['fecha_actualizacion']));
        
        // Calcular tiempo total en el sistema
        $fecha_inicio = new DateTime($pedido['fecha_creacion']);
        $fecha_actual = new DateTime();
        $diferencia = $fecha_inicio->diff($fecha_actual);
        $pedido['tiempo_total_sistema'] = $diferencia->format('%d días, %h horas, %i minutos');
        
        // Verificar si hay archivo adjunto
        $pedido['tiene_archivo'] = !empty($pedido['archivo_adjunto']);
        
        echo json_encode([
            'success' => true, 
            'pedido' => $pedido
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener detalles del pedido: ' . $e->getMessage()]);
}

$conn->close();
?>