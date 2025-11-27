<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'db_connection.php';

session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Parámetros para filtrar
$estado_id_str = $_GET['estado_id'] ?? '';
$area_id_str = $_GET['area_id'] ?? '';
$area_id_not_in_str = $_GET['area_id_not_in'] ?? '';
$area_base = $_GET['area_base'] ?? '';

// Log para depuración
error_log("get_pedidos.php - Parámetros: area_base=$area_base");

try {
    $pedidos = [];
    
    // SQL corregido basado en estructura real
    if ($area_base === 'Control de Calidad') {
        $sql = "SELECT p.id, 
                       p.numero_guia as numero_pedido, 
                       p.nombre_cliente as cliente, 
                       p.correo_cliente as correo, 
                       p.fecha_creacion, 
                       p.prioridad, 
                       p.notas, 
                       p.area_id, 
                       p.estado_id, 
                       a.nombre_area as area_nombre, 
                       e.nombre_estado as estado_nombre
                FROM pedidos p
                LEFT JOIN areas a ON p.area_id = a.id
                LEFT JOIN estados_pedido e ON p.estado_id = e.id
                WHERE p.area_id IN (16, 17, 18)
                ORDER BY CASE WHEN p.prioridad = 'alta' THEN 1 ELSE 2 END, p.fecha_creacion DESC";
    } else {
        // Consulta general (sin filtro de área)
        $sql_general = str_replace("WHERE p.area_id IN (16, 17, 18)", "", $sql);
        $sql = "";
    }
    
    error_log("get_pedidos.php - SQL: $sql");
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Verificar si tiene devoluciones
            $dev_sql = "SELECT COUNT(*) as count FROM mensajes WHERE pedido_id = ? AND tipo = 'devolucion'";
            $dev_stmt = $conn->prepare($dev_sql);
            $dev_stmt->bind_param('i', $row['id']);
            $dev_stmt->execute();
            $dev_result = $dev_stmt->get_result();
            $dev_row = $dev_result->fetch_assoc();
            
            $row['tiene_devoluciones'] = $dev_row['count'] > 0 ? 1 : 0;
            $dev_stmt->close();
            
            $pedidos[] = $row;
        }
    } else {
        error_log("get_pedidos.php - Error en consulta: " . $conn->error);
    }
    
    error_log("get_pedidos.php - Encontrados: " . count($pedidos) . " pedidos");
    
    // Asegurar que siempre devolvemos un array
    echo json_encode($pedidos);
    
} catch (Exception $e) {
    error_log("get_pedidos.php - Error: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>