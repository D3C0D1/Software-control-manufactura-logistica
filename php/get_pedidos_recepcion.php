<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

try {
    // Construir la consulta base
    $sql = "SELECT p.*, a.nombre as area_nombre, e.nombre as estado_nombre 
            FROM pedidos p 
            LEFT JOIN areas a ON p.area_id = a.id 
            LEFT JOIN estados e ON p.estado_id = e.id 
            WHERE 1=1";
    
    $params = [];
    
    // Filtrar por estado_id si se proporciona
    if (isset($_GET['estado_id']) && !empty($_GET['estado_id'])) {
        $estado_ids = explode(',', $_GET['estado_id']);
        $placeholders = str_repeat('?,', count($estado_ids) - 1) . '?';
        $sql .= " AND p.estado_id IN ($placeholders)";
        $params = array_merge($params, $estado_ids);
    }
    
    // Filtrar por area_id si se proporciona
    if (isset($_GET['area_id']) && !empty($_GET['area_id'])) {
        $area_ids = explode(',', $_GET['area_id']);
        $placeholders = str_repeat('?,', count($area_ids) - 1) . '?';
        $sql .= " AND p.area_id IN ($placeholders)";
        $params = array_merge($params, $area_ids);
    }
    
    // Filtrar por area_id_not_in si se proporciona
    if (isset($_GET['area_id_not_in']) && !empty($_GET['area_id_not_in'])) {
        $area_ids_not_in = explode(',', $_GET['area_id_not_in']);
        $placeholders = str_repeat('?,', count($area_ids_not_in) - 1) . '?';
        $sql .= " AND p.area_id NOT IN ($placeholders)";
        $params = array_merge($params, $area_ids_not_in);
    }
    
    // Ordenar por fecha de creación descendente
    $sql .= " ORDER BY p.fecha_creacion DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($pedidos);
    
} catch (Exception $e) {
    error_log("Error en get_pedidos_recepcion.php: " . $e->getMessage());
    echo json_encode([]);
}
?>