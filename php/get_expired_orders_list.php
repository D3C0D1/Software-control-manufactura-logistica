<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$type = $_GET['type'] ?? ''; 

try {
    $pdo = getPDOConnection();
    
    $sql = "SELECT p.numero_guia, p.nombre_cliente, a.nombre_area, p.fecha_creacion 
            FROM pedidos p 
            JOIN areas a ON p.area_id = a.id 
            WHERE p.area_id != 10 AND p.estado_id != 3";
            
    if ($type === 'urgent' || $type === 'urgent_expired') {
        $sql .= " AND p.prioridad = 'alta' AND p.fecha_creacion < DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $limitHours = 24;
        $isExpired = true;
    } elseif ($type === 'normal' || $type === 'normal_expired') {
        $sql .= " AND p.prioridad = 'normal' AND p.fecha_creacion < DATE_SUB(NOW(), INTERVAL 3 DAY)";
        $limitHours = 72;
        $isExpired = true;
    } elseif ($type === 'urgent_warning') {
        $sql .= " AND p.prioridad = 'alta' AND p.fecha_creacion BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND DATE_SUB(NOW(), INTERVAL 12 HOUR)";
        $limitHours = 24;
        $isExpired = false;
    } elseif ($type === 'normal_warning') {
        $sql .= " AND p.prioridad = 'normal' AND p.fecha_creacion BETWEEN DATE_SUB(NOW(), INTERVAL 72 HOUR) AND DATE_SUB(NOW(), INTERVAL 60 HOUR)";
        $limitHours = 72;
        $isExpired = false;
    } else {
        echo json_encode(['error' => 'Tipo inválido']);
        exit;
    }
    
    $sql .= " ORDER BY p.fecha_creacion ASC"; 
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process time info
    foreach ($orders as &$order) {
        $created = new DateTime($order['fecha_creacion']);
        $now = new DateTime();
        
        if ($isExpired) {
            // Calculate how long ago it expired
            $deadline = clone $created;
            $deadline->modify("+$limitHours hours");
            $diff = $deadline->diff($now);
            
            $days = $diff->days;
            $hours = $diff->h;
            $minutes = $diff->i;
            
            $timeStr = "";
            if ($days > 0) $timeStr .= "{$days}d ";
            if ($hours > 0) $timeStr .= "{$hours}h ";
            $timeStr .= "{$minutes}m";
            
            $order['tiempo_info'] = "Vencido hace: $timeStr";
            $order['status_class'] = 'expired-text';
        } else {
            // Calculate time remaining
            $deadline = clone $created;
            $deadline->modify("+$limitHours hours");
            $diff = $now->diff($deadline);
            
            $hours = ($diff->days * 24) + $diff->h;
            $minutes = $diff->i;
            
            $order['tiempo_info'] = "Vence en: {$hours}h {$minutes}m";
            $order['status_class'] = 'warning-text';
        }
        
        // Format creation date
        $order['fecha_formateada'] = $created->format('d/m/Y H:i');
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (Exception $e) {
    error_log("Error in get_expired_orders_list.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener lista']);
}
?>
