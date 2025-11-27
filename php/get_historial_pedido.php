<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pedido_id > 0) {
    try {
        // Obtener información básica del pedido
        $sql_pedido = "SELECT 
                        p.numero_guia,
                        p.nombre_cliente,
                        p.fecha_creacion,
                        p.area_id as area_actual_id,
                        a.nombre_area as area_actual,
                        p.estado_id,
                        e.nombre_estado
                       FROM pedidos p
                       LEFT JOIN areas a ON p.area_id = a.id
                       LEFT JOIN estados_pedido e ON p.estado_id = e.id
                       WHERE p.id = ?";
        
        $stmt_pedido = $conn->prepare($sql_pedido);
        $stmt_pedido->bind_param("i", $pedido_id);
        $stmt_pedido->execute();
        $pedido_info = $stmt_pedido->get_result()->fetch_assoc();
        $stmt_pedido->close();

        // Obtener historial detallado con nombres completos
        $sql = "SELECT 
                    h.fecha_entrada, 
                    h.fecha_salida, 
                    h.area_id,
                    a.nombre_area, 
                    TIMESTAMPDIFF(MINUTE, h.fecha_entrada, IFNULL(h.fecha_salida, NOW())) as duracion_minutos,
                    COALESCE(u_entrada.nombre_completo, 'Sistema') as usuario_entrada,
                    COALESCE(u_salida.nombre_completo, 'En proceso') as usuario_salida,
                    h.usuario_entrada_id,
                    h.usuario_salida_id
                FROM historial_pedidos h
                JOIN areas a ON h.area_id = a.id
                LEFT JOIN usuarios u_entrada ON h.usuario_entrada_id = u_entrada.id
                LEFT JOIN usuarios u_salida ON h.usuario_salida_id = u_salida.id
                WHERE h.pedido_id = ? 
                ORDER BY h.fecha_entrada ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $historial = [];
        $tiempo_total_proceso = 0;
        $fecha_inicio_proceso = null;
        $fecha_finalizacion = null;
        $areas_visitadas = [];
        $usuarios_por_area = [];
        
        while ($row = $result->fetch_assoc()) {
            $historial[] = $row;
            
            // Calcular tiempo total en proceso (excluyendo área 10 - Finalizado)
            if ($row['area_id'] != 10) {
                $tiempo_total_proceso += $row['duracion_minutos'];
                
                // Establecer fecha de inicio (primera área que no sea finalizado)
                if ($fecha_inicio_proceso === null) {
                    $fecha_inicio_proceso = $row['fecha_entrada'];
                }
            }
            
            // Detectar cuando llegó al área 10 (Finalizado)
            if ($row['area_id'] == 10 && $row['fecha_entrada']) {
                $fecha_finalizacion = $row['fecha_entrada'];
            }
            
            // Registrar áreas visitadas y usuarios
            $areas_visitadas[] = $row['nombre_area'];
            $usuarios_por_area[$row['nombre_area']] = [
                'usuario_entrada' => $row['usuario_entrada'],
                'usuario_salida' => $row['usuario_salida']
            ];
        }
        
        // Calcular tiempo total desde creación hasta finalización
        $tiempo_total_desde_creacion = 0;
        if ($pedido_info['fecha_creacion']) {
            $fecha_fin = $fecha_finalizacion ? $fecha_finalizacion : date('Y-m-d H:i:s');
            $tiempo_total_desde_creacion = round((strtotime($fecha_fin) - strtotime($pedido_info['fecha_creacion'])) / 60);
        }
        
        // Calcular tiempo promedio por área
        $tiempo_promedio_por_area = count($historial) > 0 ? round($tiempo_total_proceso / count($historial)) : 0;
        
        // Identificar área más lenta
        $area_mas_lenta = null;
        $tiempo_maximo = 0;
        foreach ($historial as $registro) {
            if ($registro['area_id'] != 10 && $registro['duracion_minutos'] > $tiempo_maximo) {
                $tiempo_maximo = $registro['duracion_minutos'];
                $area_mas_lenta = $registro['nombre_area'];
            }
        }
        
        // Preparar métricas adicionales
        $metricas = [
            'tiempo_total_proceso_minutos' => $tiempo_total_proceso,
            'tiempo_total_desde_creacion_minutos' => $tiempo_total_desde_creacion,
            'fecha_inicio_proceso' => $fecha_inicio_proceso,
            'fecha_finalizacion' => $fecha_finalizacion,
            'estado_actual' => $pedido_info['nombre_estado'],
            'area_actual' => $pedido_info['area_actual'],
            'areas_visitadas' => array_unique($areas_visitadas),
            'total_areas_visitadas' => count(array_unique($areas_visitadas)),
            'tiempo_promedio_por_area' => $tiempo_promedio_por_area,
            'area_mas_lenta' => $area_mas_lenta,
            'tiempo_area_mas_lenta' => $tiempo_maximo,
            'usuarios_por_area' => $usuarios_por_area,
            'esta_finalizado' => $pedido_info['area_actual_id'] == 10,
            'tiempo_total_formateado' => formatearTiempo($tiempo_total_proceso),
            'tiempo_desde_creacion_formateado' => formatearTiempo($tiempo_total_desde_creacion)
        ];
        
        echo json_encode([
            'success' => true, 
            'historial' => $historial,
            'pedido_info' => $pedido_info,
            'metricas' => $metricas
        ]);
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
}

function formatearTiempo($minutos) {
    if ($minutos < 60) {
        return $minutos . ' min';
    } elseif ($minutos < 1440) { // menos de 24 horas
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        return $horas . 'h ' . $mins . 'min';
    } else { // más de 24 horas
        $dias = floor($minutos / 1440);
        $horas = floor(($minutos % 1440) / 60);
        $mins = $minutos % 60;
        return $dias . 'd ' . $horas . 'h ' . $mins . 'min';
    }
}

$conn->close();
?>