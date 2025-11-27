<?php
session_start();
require_once 'db_connection.php';
header('Content-Type: application/json');

// Autorización: si no hay sesión, devolver datos vacíos (evita romper la UI)
$is_auth = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);

// Parámetro de límite de registros: 'all' para todos, o un número permitido
$limitParam = isset($_GET['limit']) ? $_GET['limit'] : '';
$allowedLimits = [25, 50, 100, 200];
if ($limitParam === 'all' || $limitParam === '-1') {
    $limitClause = '';
} else if (is_numeric($limitParam) && in_array(intval($limitParam), $allowedLimits, true)) {
    $limitClause = ' LIMIT ' . intval($limitParam);
} else {
    // Valor por defecto razonable
    $limitClause = ' LIMIT 200';
}

function tailFile($file, $lines = 100) {
    if (!is_readable($file)) return [];
    $f = fopen($file, 'r');
    if (!$f) return [];
    $buffer = '';
    $chunkSize = 4096;
    $pos = -1;
    $lineCount = 0;
    $stat = fstat($f);
    $size = $stat['size'];
    $data = '';
    for ($pos = $size; $pos > 0 && $lineCount <= $lines; $pos -= $chunkSize) {
        $seek = max(0, $pos - $chunkSize);
        fseek($f, $seek);
        $chunk = fread($f, $pos - $seek);
        $data = $chunk . $data;
        $lineCount = substr_count($data, "\n");
    }
    fclose($f);
    $arr = explode("\n", trim($data));
    return array_slice($arr, max(0, count($arr) - $lines));
}

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Conexión no disponible');
    }

    // Asegurar existencia de la tabla de eliminaciones para evitar errores en auditoría
    $has_deleted_table = false;
    $create_sql = "CREATE TABLE IF NOT EXISTS pedidos_eliminados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        numero_guia VARCHAR(100),
        nombre_cliente VARCHAR(255),
        numero_cliente VARCHAR(100),
        usuario_id INT,
        fecha_eliminacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (pedido_id),
        INDEX (numero_guia)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($create_sql) === TRUE) {
        $has_deleted_table = true;
    } else {
        // Si falla la creación, verificar si existe de todas formas
        $chk_sql = "SELECT COUNT(*) AS cnt FROM information_schema.tables 
                    WHERE table_schema = DATABASE() AND table_name = 'pedidos_eliminados'";
        if ($rs = $conn->query($chk_sql)) {
            $row = $rs->fetch_assoc();
            $has_deleted_table = isset($row['cnt']) && intval($row['cnt']) > 0;
            $rs->close();
        }
    }

    // Movimientos de pedidos (historial)
    $movimientos = [];
    if ($is_auth) {
        $sql_mov = "SELECT h.pedido_id, p.numero_guia, p.nombre_cliente, p.numero_cliente,
                            h.area_id, a.nombre_area AS area_nombre,
                            h.usuario_entrada_id, u.nombre_completo AS usuario_nombre,
                            h.fecha_entrada, h.fecha_salida, h.tiempo_en_area,
                            'movimiento' AS tipo_evento
                     FROM historial_pedidos h
                     LEFT JOIN pedidos p ON p.id = h.pedido_id
                     LEFT JOIN areas a ON a.id = h.area_id
                     LEFT JOIN usuarios u ON u.id = h.usuario_entrada_id
                     ORDER BY h.fecha_entrada DESC" . $limitClause;
        if ($stmt = $conn->prepare($sql_mov)) {
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $movimientos[] = $row;
            }
            $stmt->close();
        }
    }

    // Eventos de eliminación
    if ($is_auth && $has_deleted_table) {
        $sql_del = "SELECT e.pedido_id, e.numero_guia, e.nombre_cliente, e.numero_cliente,
                            NULL AS area_id, 'Eliminado' AS area_nombre,
                            e.usuario_id AS usuario_entrada_id, u.nombre_completo AS usuario_nombre,
                            e.fecha_eliminacion AS fecha_entrada, NULL AS fecha_salida, NULL AS tiempo_en_area,
                            'eliminado' AS tipo_evento
                     FROM pedidos_eliminados e
                     LEFT JOIN usuarios u ON u.id = e.usuario_id
                     ORDER BY e.fecha_eliminacion DESC" . $limitClause;
        if ($stmt3 = $conn->prepare($sql_del)) {
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            while ($row = $res3->fetch_assoc()) {
                $movimientos[] = $row;
            }
            $stmt3->close();
        }
    }

    // Actividad de usuarios (última actividad)
    $usuarios = [];
    if ($is_auth) {
        $sql_users = "SELECT u.id, u.usuario, u.nombre_completo, r.nombre_rol AS rol,
                             a.nombre AS area_empleado, u.last_activity
                      FROM usuarios u
                      LEFT JOIN roles r ON r.id = u.rol_id
                      LEFT JOIN area_empleado a ON a.id = u.area_id
                      ORDER BY COALESCE(u.last_activity, NOW()) DESC";
        if ($stmt2 = $conn->prepare($sql_users)) {
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($row = $res2->fetch_assoc()) {
                $usuarios[] = $row;
            }
            $stmt2->close();
        }
    }

    // Registros de desarrollador: último 100 líneas del archivo de errores
    $log_path = __DIR__ . '/../logs/php_errors.log';
    $logs = tailFile($log_path, 100);
    // Extraer líneas de error/advertencia para un registro específico
    $errors = [];
    foreach ($logs as $line) {
        if (preg_match('/error|warning|fatal|exception/i', $line)) {
            $errors[] = $line;
        }
    }

    echo json_encode([
        'success' => true,
        'movimientos' => $movimientos,
        'usuarios' => $usuarios,
        'logs' => $logs,
        'errors' => $errors,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} finally {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->close();
    }
}
?>