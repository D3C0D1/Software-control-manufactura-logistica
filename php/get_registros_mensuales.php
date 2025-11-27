<?php
// Solo incluir auth.php que ya incluye db_connection.php
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('No database connection');
    }
    
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    
    $sql = "
        SELECT 
            mes,
            CASE mes
                WHEN 1 THEN 'Enero'
                WHEN 2 THEN 'Febrero'
                WHEN 3 THEN 'Marzo'
                WHEN 4 THEN 'Abril'
                WHEN 5 THEN 'Mayo'
                WHEN 6 THEN 'Junio'
                WHEN 7 THEN 'Julio'
                WHEN 8 THEN 'Agosto'
                WHEN 9 THEN 'Septiembre'
                WHEN 10 THEN 'Octubre'
                WHEN 11 THEN 'Noviembre'
                WHEN 12 THEN 'Diciembre'
            END as mes_nombre,
            total,
            en_proceso,
            finalizados,
            eliminados
        FROM (
            SELECT 
                MONTH(fecha_creacion) as mes,
                COUNT(*) as total,
                SUM(CASE WHEN estado_id = 1 THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado_id = 2 THEN 1 ELSE 0 END) as finalizados,
                SUM(CASE WHEN estado_id = 3 THEN 1 ELSE 0 END) as eliminados
            FROM pedidos 
            WHERE YEAR(fecha_creacion) = ?
            GROUP BY MONTH(fecha_creacion)
        ) as monthly_data
        ORDER BY mes DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    if ($limit) {
        $stmt->bind_param("ii", $year, $limit);
    } else {
        $stmt->bind_param("i", $year);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $records[] = [
                'mes' => $row['mes'],
                'mes_nombre' => $row['mes_nombre'],
                'total' => intval($row['total']),
                'en_proceso' => intval($row['en_proceso']),
                'finalizados' => intval($row['finalizados']),
                'eliminados' => intval($row['eliminados'])
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_registros_mensuales.php: " . $e->getMessage() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener registros mensuales',
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>