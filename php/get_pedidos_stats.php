<?php
header('Content-Type: application/json');

require_once 'db_connection.php';

session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // Inicializar conexión PDO
    $pdo = getPDOConnection();
    // Total de pedidos
    $sql_total = "SELECT COUNT(*) as count FROM pedidos";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute();
    $total_pedidos = $stmt_total->fetch(PDO::FETCH_ASSOC)['count'];
    // Contador de pedidos con guías generadas (área recepción, estado 1)
    $sql_guias = "SELECT COUNT(*) as count FROM pedidos WHERE area_id = 20 AND estado_id = 1";
    $stmt_guias = $pdo->prepare($sql_guias);
    $stmt_guias->execute();
    $guias_generadas = $stmt_guias->fetch(PDO::FETCH_ASSOC)['count'];

    // Contador de pedidos en proceso:
    // incluir pedidos en áreas operativas con estados 1 (recién en área) o 2 (en proceso),
    // y excluir Finalizados (10), Recepción (20) y Eliminados (estado 3)
    $sql_proceso = "SELECT COUNT(*) as count FROM pedidos WHERE area_id NOT IN (10, 20) AND estado_id IN (1, 2)";
    $stmt_proceso = $pdo->prepare($sql_proceso);
    $stmt_proceso->execute();
    $pedidos_proceso = $stmt_proceso->fetch(PDO::FETCH_ASSOC)['count'];

    // Contador de pedidos eliminados (estado 3)
    $sql_eliminados = "SELECT COUNT(*) as count FROM pedidos WHERE estado_id = 3";
    $stmt_eliminados = $pdo->prepare($sql_eliminados);
    $stmt_eliminados->execute();
    $pedidos_eliminados = $stmt_eliminados->fetch(PDO::FETCH_ASSOC)['count'];

    // Contador de pedidos devueltos (que tienen mensajes de devolución)
    $sql_devueltos = "SELECT COUNT(DISTINCT p.id) as count 
                      FROM pedidos p 
                      INNER JOIN mensajes_devolucion md ON p.id = md.pedido_id";
    $stmt_devueltos = $pdo->prepare($sql_devueltos);
    $stmt_devueltos->execute();
    $pedidos_devueltos = $stmt_devueltos->fetch(PDO::FETCH_ASSOC)['count'];

    // Contador de pedidos no devueltos (que NO tienen mensajes de devolución)
    $sql_no_devueltos = "SELECT COUNT(*) as count 
                         FROM pedidos p 
                         LEFT JOIN mensajes_devolucion md ON p.id = md.pedido_id 
                         WHERE md.pedido_id IS NULL";
    $stmt_no_devueltos = $pdo->prepare($sql_no_devueltos);
    $stmt_no_devueltos->execute();
    $pedidos_no_devueltos = $stmt_no_devueltos->fetch(PDO::FETCH_ASSOC)['count'];

    // Contador de pedidos finalizados (área_id = 10)
    $sql_finalizados = "SELECT COUNT(*) as count FROM pedidos WHERE area_id = 10";
    $stmt_finalizados = $pdo->prepare($sql_finalizados);
    $stmt_finalizados->execute();
    $pedidos_finalizados = $stmt_finalizados->fetch(PDO::FETCH_ASSOC)['count'];

    // Respuesta con todos los contadores
    $response = [
        'total' => (int)$total_pedidos,
        'guias_generadas' => (int)$guias_generadas,
        'pedidos_proceso' => (int)$pedidos_proceso,
        'pedidos_eliminados' => (int)$pedidos_eliminados,
        'pedidos_devueltos' => (int)$pedidos_devueltos,
        'pedidos_no_devueltos' => (int)$pedidos_no_devueltos,
        'finalizados' => (int)$pedidos_finalizados
    ];

    echo json_encode($response);

} catch (Throwable $e) {
    error_log("Error en get_pedidos_stats.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener estadísticas']);
}
?>