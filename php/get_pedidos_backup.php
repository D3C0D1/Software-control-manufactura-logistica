<?php
header('Content-Type: application/json');

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

// Log para depuración
error_log("get_pedidos.php - Parámetros recibidos: estado_id=$estado_id_str, area_id=$area_id_str, area_id_not_in=$area_id_not_in_str");

$sql = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.fecha_creacion, p.prioridad, 
               a.id as area_id, a.nombre_area as area_nombre, p.estado_id, e.nombre_estado,
               CASE 
                   WHEN p.area_id = 12 AND p.estado_id = 2 THEN (
                       SELECT u.nombre_completo 
                       FROM historial_pedidos h 
                       JOIN usuarios u ON h.usuario_entrada_id = u.id 
                       WHERE h.pedido_id = p.id AND h.area_id = 12 
                       ORDER BY h.fecha_entrada DESC 
                       LIMIT 1
                   )
                   ELSE NULL
               END as usuario_proceso_mensajeria,
               CASE 
                   WHEN p.area_id = 5 THEN (
                       SELECT u.nombre_completo 
                       FROM historial_pedidos h 
                       JOIN usuarios u ON h.usuario_entrada_id = u.id 
                       WHERE h.pedido_id = p.id AND h.area_id = 5 
                       ORDER BY h.fecha_entrada DESC 
                       LIMIT 1
                   )
                   ELSE NULL
               END as usuario_proceso_impresion,
               CASE 
                   WHEN p.area_id = 7 THEN (
                       SELECT u.nombre_completo 
                       FROM historial_pedidos h 
                       JOIN usuarios u ON h.usuario_entrada_id = u.id 
                       WHERE h.pedido_id = p.id AND h.area_id = 7 
                       ORDER BY h.fecha_entrada DESC 
                       LIMIT 1
                   )
                   ELSE NULL
               END as usuario_proceso_sublimado,
               CASE 
                   WHEN p.area_id = 10 THEN (
                       SELECT u.nombre_completo 
                       FROM historial_pedidos h 
                       JOIN usuarios u ON h.usuario_entrada_id = u.id 
                       WHERE h.pedido_id = p.id AND h.area_id = 10 
                       ORDER BY h.fecha_entrada DESC 
                       LIMIT 1
                   )
                   ELSE NULL
               END as usuario_proceso_confeccion,
               CASE 
                   WHEN p.area_id = 17 THEN (
                       SELECT u.nombre_completo 
                       FROM historial_pedidos h 
                       JOIN usuarios u ON h.usuario_entrada_id = u.id 
                       WHERE h.pedido_id = p.id AND h.area_id = 17 
                       ORDER BY h.fecha_entrada DESC 
                       LIMIT 1
                   )
                   ELSE NULL
               END as usuario_proceso_control_calidad
        FROM pedidos p
        JOIN areas a ON p.area_id = a.id
        LEFT JOIN estados_pedido e ON p.estado_id = e.id";}]}}}

$params = [];
$types = '';
$conditions = [];

// Filtro por estado_id
if (!empty($estado_id_str)) {
    $estado_ids = explode(',', $estado_id_str);
    if (!empty($estado_ids)) {
        $placeholders = implode(',', array_fill(0, count($estado_ids), '?'));
        $conditions[] = "p.estado_id IN ($placeholders)";
        foreach ($estado_ids as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($estado_ids));
    }
}

// Filtro por area_id
if (!empty($area_id_str)) {
    $area_ids = explode(',', $area_id_str);
    if (!empty($area_ids)) {
        $placeholders = implode(',', array_fill(0, count($area_ids), '?'));
        $conditions[] = "p.area_id IN ($placeholders)";
        foreach ($area_ids as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($area_ids));
    }
}

// Filtro por area_id NOT IN
if (!empty($area_id_not_in_str)) {
    $area_ids_not_in = explode(',', $area_id_not_in_str);
    if (!empty($area_ids_not_in)) {
        $placeholders = implode(',', array_fill(0, count($area_ids_not_in), '?'));
        $conditions[] = "p.area_id NOT IN ($placeholders)";
        foreach ($area_ids_not_in as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($area_ids_not_in));
    }
}

// Agregar condiciones WHERE si existen
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY p.fecha_creacion DESC";

// Log de la consulta SQL
error_log("get_pedidos.php - SQL: $sql");
error_log("get_pedidos.php - Parámetros: " . print_r($params, true));

$stmt = $conn->prepare($sql);

if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    $pedidos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
    }
    
    // Log del resultado
    error_log("get_pedidos.php - Pedidos encontrados: " . count($pedidos));
    
    echo json_encode($pedidos);
    $stmt->close();
} else {
    $error_msg = 'Error en la consulta SQL: ' . $conn->error;
    error_log("get_pedidos.php - " . $error_msg);
    echo json_encode(['error' => $error_msg]);
}

$conn->close();
?>
