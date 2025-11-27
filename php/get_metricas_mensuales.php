<?php
// Solo incluir auth.php que ya incluye db_connection.php
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('No database connection');
    }
    
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Obtener total de pedidos del mes actual
    $currentMonth = date('n');
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pedidos 
        WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?
    ");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    $stmt->bind_param("ii", $year, $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    $row = $result->fetch_assoc();
    $totalMesActual = $row ? intval($row['total']) : 0;
    
    // Obtener promedio mensual del año
    $stmt = $conn->prepare("
        SELECT AVG(monthly_count) as promedio
        FROM (
            SELECT COUNT(*) as monthly_count
            FROM pedidos 
            WHERE YEAR(fecha_creacion) = ?
            GROUP BY MONTH(fecha_creacion)
        ) as monthly_totals
    ");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    $row = $result->fetch_assoc();
    $promedioMensual = $row ? round($row['promedio'] ?? 0) : 0;
    
    // Obtener el mejor mes
    $stmt = $conn->prepare("
        SELECT mes, total,
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
               END as mes_nombre
        FROM (
            SELECT MONTH(fecha_creacion) as mes, COUNT(*) as total
            FROM pedidos 
            WHERE YEAR(fecha_creacion) = ?
            GROUP BY MONTH(fecha_creacion)
        ) as monthly_data
        ORDER BY total DESC
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    $mejorMes = $result->fetch_assoc();
    $mejorMesTexto = $mejorMes ? $mejorMes['mes_nombre'] . ' (' . $mejorMes['total'] . ')' : '-';
    
    // Calcular crecimiento vs mes anterior
    $mesAnterior = $currentMonth - 1;
    $yearAnterior = $year;
    if ($mesAnterior == 0) {
        $mesAnterior = 12;
        $yearAnterior = $year - 1;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pedidos 
        WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?
    ");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    $stmt->bind_param("ii", $yearAnterior, $mesAnterior);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result');
    }
    $row = $result->fetch_assoc();
    $totalMesAnterior = $row ? intval($row['total']) : 0;
    
    $crecimiento = 0;
    if ($totalMesAnterior > 0) {
        $crecimiento = round((($totalMesActual - $totalMesAnterior) / $totalMesAnterior) * 100, 1);
    }
    $crecimientoTexto = ($crecimiento >= 0 ? '+' : '') . $crecimiento . '%';
    
    // Datos para el gráfico (últimos 12 meses)
    $chartData = [];
    for ($i = 11; $i >= 0; $i--) {
        $targetMonth = $currentMonth - $i;
        $targetYear = $year;
        
        if ($targetMonth <= 0) {
            $targetMonth += 12;
            $targetYear = $year - 1;
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM pedidos 
            WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?
        ");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        $stmt->bind_param("ii", $targetYear, $targetMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception('Failed to get result');
        }
        $row = $result->fetch_assoc();
        $monthTotal = $row ? intval($row['total']) : 0;
        
        $monthNames = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 
                      'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        $chartData[] = [
            'month' => $monthNames[$targetMonth],
            'total' => $monthTotal
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total_mes_actual' => $totalMesActual,
        'promedio_mensual' => $promedioMensual,
        'mejor_mes' => $mejorMesTexto,
        'crecimiento_mensual' => $crecimientoTexto,
        'chart_data' => $chartData
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_metricas_mensuales.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener métricas mensuales',
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>