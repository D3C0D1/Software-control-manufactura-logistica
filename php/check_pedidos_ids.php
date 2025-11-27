<?php
// Diagnóstico de IDs en la tabla pedidos y estado de AUTO_INCREMENT
// Intenta múltiples métodos de conexión en local (MAMP/LAMP) y producción.

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

$username = DB_USER;
$password = DB_PASS;
$dbname   = DB_NAME;

$attempts = [
    // Usa la configuración declarada (host/puerto)
    [DB_HOST, defined('DB_PORT') ? DB_PORT : null],
    // Forzar TCP sin socket para evitar "No such file or directory"
    ['127.0.0.1', 3306],
    ['127.0.0.1', 8889],
    ['localhost', 3306],
    ['localhost', 8889],
];

$conn = null;
$used = null;
$errors = [];

foreach ($attempts as $i => $cfg) {
    [$host, $port] = $cfg;
    try {
        if ($port === null) {
            $conn = new mysqli($host, $username, $password, $dbname);
        } else {
            $conn = new mysqli($host, $username, $password, $dbname, $port);
        }
        if ($conn && !$conn->connect_error) {
            $used = ['host' => $host, 'port' => $port ?? 'default'];
            break;
        } else {
            $errors[] = "Intento #$i fallo: " . ($conn ? $conn->connect_error : 'sin objeto conn');
        }
    } catch (Throwable $e) {
        $errors[] = "Intento #$i exception: " . $e->getMessage();
    }
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No fue posible conectar a la base de datos',
        'db' => $dbname,
        'tried' => $attempts,
        'errors' => $errors,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Configurar zona horaria
$conn->query("SET time_zone = '-05:00'");

// Recolectar métricas de pedidos
$data = [
    'success' => true,
    'db' => $dbname,
    'connection_used' => $used,
    'pedidos' => [],
];

try {
    // Rangos básicos
    $sqlBounds = "SELECT MIN(id) AS min_id, MAX(id) AS max_id, COUNT(*) AS total FROM pedidos";
    $resBounds = $conn->query($sqlBounds);
    if ($resBounds) {
        $data['pedidos']['bounds'] = $resBounds->fetch_assoc();
        $resBounds->close();
    }

    // Estado del AUTO_INCREMENT
    $sqlAI = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'";
    $resAI = $conn->query($sqlAI);
    if ($resAI) {
        $rowAI = $resAI->fetch_assoc();
        $data['pedidos']['next_auto_increment'] = $rowAI['AUTO_INCREMENT'] ?? null;
        $resAI->close();
    }

    // ¿La columna id tiene auto_increment?
    $sqlCol = "SELECT COLUMN_TYPE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id'";
    $resCol = $conn->query($sqlCol);
    if ($resCol) {
        $data['pedidos']['id_column'] = $resCol->fetch_assoc();
        $resCol->close();
    }

    // Primer y último por fecha_creacion si existe
    try {
        $sqlFirst = "SELECT id, fecha_creacion FROM pedidos ORDER BY fecha_creacion ASC LIMIT 1";
        $resFirst = $conn->query($sqlFirst);
        if ($resFirst) {
            $data['pedidos']['first_by_fecha'] = $resFirst->fetch_assoc();
            $resFirst->close();
        }
        $sqlLast = "SELECT id, fecha_creacion FROM pedidos ORDER BY fecha_creacion DESC LIMIT 1";
        $resLast = $conn->query($sqlLast);
        if ($resLast) {
            $data['pedidos']['last_by_fecha'] = $resLast->fetch_assoc();
            $resLast->close();
        }
    } catch (Throwable $e) {
        $data['pedidos']['fecha_creacion_info'] = 'Columna ausente o error consultando: ' . $e->getMessage();
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error consultando métricas de pedidos: ' . $e->getMessage(),
        'connection_used' => $used,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

// Cerrar conexión
if ($conn instanceof mysqli) { $conn->close(); }

?>