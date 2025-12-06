<?php
// Limpiar cualquier output previo
ob_clean();

// Configurar headers antes de cualquier output
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Suprimir errores y warnings
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once 'db_connection.php';
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

function getPedidosMetrics($conn) {

    // Total Pedidos
    $sql_total = "SELECT COUNT(*) as total FROM pedidos";
    $result_total = $conn->query($sql_total);
    $total_pedidos = $result_total ? $result_total->fetch_assoc()['total'] : 0;

    // Pedidos Finalizados (área ID 10 - Guia Generada)
    $sql_finished = "SELECT COUNT(*) as finished FROM pedidos WHERE area_id = 10";
    $result_finished = $conn->query($sql_finished);
    $finished_pedidos = $result_finished ? $result_finished->fetch_assoc()['finished'] : 0;

    // Pedidos en Recepción (área ID 20 - Finalizado)
    $sql_reception = "SELECT COUNT(*) as reception FROM pedidos WHERE area_id = 20 AND estado_id = 1";
    $result_reception = $conn->query($sql_reception);
    $reception_pedidos = $result_reception ? $result_reception->fetch_assoc()['reception'] : 0;

    // Pedidos del Día
    $sql_daily = "SELECT COUNT(*) as daily FROM pedidos WHERE DATE(fecha_creacion) = CURDATE()";
    $result_daily = $conn->query($sql_daily);
    $daily_pedidos = $result_daily ? $result_daily->fetch_assoc()['daily'] : 0;

    // PQRS Totales
    $sql_total_pqrs = "SELECT COUNT(*) as total_pqrs FROM pqrs";
    $result_total_pqrs = $conn->query($sql_total_pqrs);
    $total_pqrs = $result_total_pqrs ? $result_total_pqrs->fetch_assoc()['total_pqrs'] : 0;

    // PQRS Abiertas
    $sql_open_pqrs = "SELECT COUNT(*) as open_pqrs FROM pqrs WHERE estado = 'abierto' OR estado = 'pendiente'";
    $result_open_pqrs = $conn->query($sql_open_pqrs);
    $open_pqrs = $result_open_pqrs ? $result_open_pqrs->fetch_assoc()['open_pqrs'] : 0;

    // Pedidos Urgentes Caducados (> 24 horas)
    $sql_urgent_expired = "SELECT COUNT(*) as count FROM pedidos 
                           WHERE prioridad = 'alta' 
                           AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 1 DAY)
                           AND area_id != 10 
                           AND estado_id != 3";
    $result_urgent = $conn->query($sql_urgent_expired);
    $urgent_expired = $result_urgent ? $result_urgent->fetch_assoc()['count'] : 0;

    // Pedidos Normales Caducados (> 3 días)
    $sql_normal_expired = "SELECT COUNT(*) as count FROM pedidos 
                           WHERE prioridad = 'normal' 
                           AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 3 DAY)
                           AND area_id != 10 
                           AND estado_id != 3";
    $result_normal = $conn->query($sql_normal_expired);
    $normal_expired = $result_normal ? $result_normal->fetch_assoc()['count'] : 0;

    // Pedidos Urgentes Por Caducar (Entre 12 y 24 horas - Menos de 12h restantes)
    $sql_urgent_warning = "SELECT COUNT(*) as count FROM pedidos 
                           WHERE prioridad = 'alta' 
                           AND fecha_creacion BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND DATE_SUB(NOW(), INTERVAL 12 HOUR)
                           AND area_id != 10 
                           AND estado_id != 3";
    $result_urgent_warning = $conn->query($sql_urgent_warning);
    $urgent_warning = $result_urgent_warning ? $result_urgent_warning->fetch_assoc()['count'] : 0;

    // Pedidos Normales Por Caducar (Entre 60 y 72 horas - 2.5 a 3 días)
    $sql_normal_warning = "SELECT COUNT(*) as count FROM pedidos 
                           WHERE prioridad = 'normal' 
                           AND fecha_creacion BETWEEN DATE_SUB(NOW(), INTERVAL 72 HOUR) AND DATE_SUB(NOW(), INTERVAL 60 HOUR)
                           AND area_id != 10 
                           AND estado_id != 3";
    $result_normal_warning = $conn->query($sql_normal_warning);
    $normal_warning = $result_normal_warning ? $result_normal_warning->fetch_assoc()['count'] : 0;

    // PQRS Cerradas
    $sql_closed_pqrs = "SELECT COUNT(*) as closed_pqrs FROM pqrs WHERE estado = 'cerrado' OR estado = 'resuelto'";
    $result_closed_pqrs = $conn->query($sql_closed_pqrs);
    $closed_pqrs = $result_closed_pqrs ? $result_closed_pqrs->fetch_assoc()['closed_pqrs'] : 0;

    // Empleados Activos
    $sql_active_employees = "SELECT COUNT(*) as active_employees FROM usuarios WHERE rol_id = 3";
    $result_active_employees = $conn->query($sql_active_employees);
    $active_employees = $result_active_employees ? $result_active_employees->fetch_assoc()['active_employees'] : 0;

    // Usuarios Registrados (incluye admin, operadores y empleados)
    $sql_registered_employees = "SELECT COUNT(*) as registered_employees FROM usuarios WHERE rol_id IN (1, 2, 3)";
    $result_registered_employees = $conn->query($sql_registered_employees);
    $registered_employees = $result_registered_employees ? $result_registered_employees->fetch_assoc()['registered_employees'] : 0;

    // Usuarios con Sesión Iniciada (activos en los últimos 5 minutos - tiempo real)
    $sql_logged_users = "SELECT COUNT(*) as logged_users FROM usuarios WHERE 
                         last_activity IS NOT NULL AND TIMESTAMPDIFF(MINUTE, last_activity, NOW()) <= 5";
    $result_logged_users = $conn->query($sql_logged_users);
    $logged_users = $result_logged_users ? $result_logged_users->fetch_assoc()['logged_users'] : 0;

    // Obtener detalles de usuarios con sesión iniciada (solo usuarios realmente activos)
    $sql_logged_details = "SELECT u.id, u.nombre_completo, u.usuario, r.nombre_rol as rol, 
                           COALESCE(a.nombre, 'Sin área') as area_empleado,
                           u.last_activity as ultima_actividad,
                           CASE 
                               WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 1 THEN 'online'
                               WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 5 THEN 'active'
                               ELSE 'away'
                           END as status
                           FROM usuarios u 
                           JOIN roles r ON u.rol_id = r.id 
                           LEFT JOIN area_empleado a ON u.area_id = a.id
                           WHERE u.last_activity IS NOT NULL AND TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 5
                           ORDER BY u.last_activity DESC, u.nombre_completo";
    $result_logged_details = $conn->query($sql_logged_details);
    $logged_users_details = [];
    if ($result_logged_details) {
        while ($row = $result_logged_details->fetch_assoc()) {
            $logged_users_details[] = $row;
        }
    }

    // Función para obtener desglose por estado de un área
    function getAreaBreakdown($conn, $area_ids) {
        $breakdown = [
            'recepcion' => 0,
            'proceso' => 0, 
            'preparado' => 0,
            'total' => 0
        ];
        
        // Recepción (primer ID del área)
        $sql_recepcion = "SELECT COUNT(*) as count FROM pedidos WHERE area_id = " . $area_ids[0];
        $result = $conn->query($sql_recepcion);
        $breakdown['recepcion'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Proceso (segundo ID del área)
        $sql_proceso = "SELECT COUNT(*) as count FROM pedidos WHERE area_id = " . $area_ids[1];
        $result = $conn->query($sql_proceso);
        $breakdown['proceso'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Preparado (tercer ID del área)
        $sql_preparado = "SELECT COUNT(*) as count FROM pedidos WHERE area_id = " . $area_ids[2];
        $result = $conn->query($sql_preparado);
        $breakdown['preparado'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Total del área
        $breakdown['total'] = $breakdown['recepcion'] + $breakdown['proceso'] + $breakdown['preparado'];
        
        return $breakdown;
    }

    // Pedidos en Recepción con Guia Generada (área ID 20)
    $sql_guia_generada = "SELECT COUNT(*) as guia_generada FROM pedidos WHERE area_id = 20 AND estado_id = 1";
    $result_guia_generada = $conn->query($sql_guia_generada);
    $guia_generada_pedidos = $result_guia_generada ? $result_guia_generada->fetch_assoc()['guia_generada'] : 0;

    // Conteo de pedidos por área con desglose detallado
    $area_counts = [
        'diseno' => getAreaBreakdown($conn, [1, 2, 3]),
        'confeccion' => getAreaBreakdown($conn, [4, 5, 6]),
        'sublimado' => getAreaBreakdown($conn, [7, 8, 9]),
        'mensajeria' => getAreaBreakdown($conn, [11, 12, 13]),
        'impresion' => getAreaBreakdown($conn, [14, 15, 16]),
        'control_calidad' => getAreaBreakdown($conn, [17, 18, 19]),
        // Recepción solo debe mostrar pedidos con Guia Generada (area_id = 20)
        'recepcion' => [
            'recepcion' => $guia_generada_pedidos, // Pedidos con Guia Generada (area_id = 20)
            'proceso' => 0,
            'preparado' => 0,
            'total' => $guia_generada_pedidos
        ]
    ];

    return [
        'total_orders' => $total_pedidos,
        'finished_orders' => $finished_pedidos,
        'reception_orders' => $reception_pedidos,
        'daily_orders' => $daily_pedidos,
        'total_pqrs' => $total_pqrs,
        'open_pqrs' => $open_pqrs,
        'closed_pqrs' => $closed_pqrs,
        'active_employees' => $active_employees,
        'registered_employees' => $registered_employees,
        'logged_users' => $logged_users,
        'logged_users_details' => $logged_users_details,
        'urgent_expired' => $urgent_expired,
        'normal_expired' => $normal_expired,
        'urgent_warning' => $urgent_warning,
        'normal_warning' => $normal_warning,
        'area_counts' => $area_counts
    ];
}

try {
    // Limpiar output buffer antes de enviar JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    echo json_encode(getPedidosMetrics($conn));
} catch (Exception $e) {
    echo json_encode(['error' => 'Error fetching metrics: ' . $e->getMessage()]);
}

// Asegurar que no hay output adicional
exit;
?>
