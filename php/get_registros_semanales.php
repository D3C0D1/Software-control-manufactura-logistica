<?php
// Solo incluir auth.php que ya incluye db_connection.php
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('No database connection');
    }
    
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    
    $sql = "
        SELECT 
            WEEK(fecha_creacion, 1) - 31 as semana_relativa,
            DATE(DATE_SUB(MIN(fecha_creacion), INTERVAL WEEKDAY(MIN(fecha_creacion)) DAY)) as inicio_semana,
            DATE(DATE_ADD(DATE_SUB(MIN(fecha_creacion), INTERVAL WEEKDAY(MIN(fecha_creacion)) DAY), INTERVAL 6 DAY)) as fin_semana,
            COUNT(*) as total,
            SUM(CASE WHEN estado_id = 1 THEN 1 ELSE 0 END) as en_proceso,
            SUM(CASE WHEN estado_id = 2 THEN 1 ELSE 0 END) as finalizados,
            SUM(CASE WHEN estado_id = 3 THEN 1 ELSE 0 END) as eliminados
        FROM pedidos 
        WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?
        GROUP BY 
            WEEK(fecha_creacion, 1),
            WEEK(fecha_creacion, 1) - 31
        ORDER BY MIN(fecha_creacion) DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    if ($limit) {
        $stmt->bind_param("iii", $year, $month, $limit);
    } else {
        $stmt->bind_param("ii", $year, $month);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $inicioSemana = new DateTime($row['inicio_semana']);
            $finSemana = new DateTime($row['fin_semana']);
            
            $records[] = [
                'semana' => intval($row['semana_relativa']),
                'rango_fechas' => $inicioSemana->format('d/m') . ' - ' . $finSemana->format('d/m'),
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
    error_log("Error in get_registros_semanales.php: " . $e->getMessage() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener registros semanales',
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>