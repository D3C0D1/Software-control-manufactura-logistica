<?php
// Corrige AUTO_INCREMENT en tablas clave y ajusta el puntero a MAX(id)+1
// Tablas: pedidos, historial_pedidos, pedido_adjuntos

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

$username = DB_USER;
$password = DB_PASS;
$dbname   = DB_NAME;

$attempts = [
    [DB_HOST, null],
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
    echo json_encode(['success' => false, 'message' => 'No fue posible conectar a la base de datos', 'errors' => $errors]);
    exit;
}

$conn->query("SET time_zone = '-05:00'");

$tables = ['pedidos', 'historial_pedidos', 'pedido_adjuntos'];
$result = [
    'success' => true,
    'connection_used' => $used,
    'changes' => [],
];

foreach ($tables as $table) {
    $info = ['table' => $table, 'status' => 'checked'];
    try {
        // Obtener estado actual de columna id
        $sqlCol = "SELECT COLUMN_TYPE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = 'id'";
        $resCol = $conn->query($sqlCol);
        $col = $resCol ? $resCol->fetch_assoc() : null;
        if ($resCol) { $resCol->close(); }
        $info['column'] = $col;

        // Obtener si id es PRIMARY KEY
        $sqlPk = "SELECT k.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.TABLE_CONSTRAINTS c ON k.CONSTRAINT_NAME = c.CONSTRAINT_NAME AND k.TABLE_SCHEMA = c.TABLE_SCHEMA AND k.TABLE_NAME = c.TABLE_NAME WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = '$table' AND k.COLUMN_NAME = 'id' AND c.CONSTRAINT_TYPE = 'PRIMARY KEY'";
        $resPk = $conn->query($sqlPk);
        $isPk = ($resPk && $resPk->num_rows > 0);
        if ($resPk) { $resPk->close(); }
        $info['is_primary_key'] = $isPk;

        // Calcular next_id
        $next_id = 1;
        $resMax = $conn->query("SELECT IFNULL(MAX(id),0)+1 AS next_id FROM $table");
        if ($resMax) {
            $row = $resMax->fetch_assoc();
            if ($row && isset($row['next_id'])) { $next_id = (int)$row['next_id']; }
            $resMax->close();
        }
        $info['next_id'] = $next_id;

        // Si no es AUTO_INCREMENT, intentar modificar
        $needsAI = !$col || !isset($col['EXTRA']) || stripos($col['EXTRA'], 'auto_increment') === false;
        if ($needsAI) {
            // Asegurar que id sea PRIMARY KEY
            if (!$isPk) {
                // Intentar agregar PK sobre id
                try {
                    $conn->query("ALTER TABLE $table ADD PRIMARY KEY (id)");
                    $info['added_primary_key'] = true;
                    $isPk = true;
                } catch (Throwable $e) {
                    $info['add_pk_error'] = $e->getMessage();
                }
            }

            // Modificar columna a AUTO_INCREMENT
            try {
                $conn->query("ALTER TABLE $table MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT");
                $info['set_auto_increment'] = true;
            } catch (Throwable $e) {
                $info['set_ai_error'] = $e->getMessage();
            }
        } else {
            $info['set_auto_increment'] = false;
        }

        // Ajustar puntero AUTO_INCREMENT
        try {
            $conn->query("ALTER TABLE $table AUTO_INCREMENT = $next_id");
            $info['auto_increment_pointer'] = $next_id;
        } catch (Throwable $e) {
            $info['set_pointer_error'] = $e->getMessage();
        }

        // Leer estado final
        $resAI = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
        if ($resAI) {
            $rowAI = $resAI->fetch_assoc();
            $info['final_auto_increment'] = $rowAI['AUTO_INCREMENT'] ?? null;
            $resAI->close();
        }
        $resCol2 = $conn->query($sqlCol);
        if ($resCol2) {
            $info['final_column'] = $resCol2->fetch_assoc();
            $resCol2->close();
        }

    } catch (Throwable $e) {
        $info['error'] = $e->getMessage();
    }
    $result['changes'][] = $info;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($conn instanceof mysqli) { $conn->close(); }

?>