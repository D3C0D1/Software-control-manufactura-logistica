<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

$out = [
    'success' => true,
    'db' => DB_NAME ?? null,
    'indexes' => [],
    'explain' => [],
    'errors' => []
];

try {
    // Crear conexión con intentos múltiples (evitar die() de db_connection)
    if (!isset($conn) || !($conn instanceof mysqli)) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $attempts = [
            [DB_HOST ?? 'localhost', null],
            ['127.0.0.1', 3306],
            ['127.0.0.1', 8889],
            ['localhost', 3306],
            ['localhost', 8889],
        ];
        $connected = false;
        foreach ($attempts as $cfg) {
            [$host, $port] = $cfg;
            try {
                $conn = $port === null
                    ? new mysqli($host, DB_USER, DB_PASS, DB_NAME)
                    : new mysqli($host, DB_USER, DB_PASS, DB_NAME, $port);
                if ($conn && !$conn->connect_error) {
                    $connected = true;
                    break;
                }
            } catch (Throwable $e) {
                $out['errors'][] = 'Intento conexión ' . $host . ':' . ($port ?? 'default') . ' -> ' . $e->getMessage();
            }
        }
        if (!$connected || !($conn instanceof mysqli)) {
            throw new Exception('Conexión inválida tras intentos múltiples');
        }
        $conn->query("SET time_zone = '-05:00'");
    }

    // Listar índices actuales
    $tables = ['pedidos', 'historial_pedidos', 'mensajes_devolucion'];
    foreach ($tables as $t) {
        try {
            $res = $conn->query("SHOW INDEX FROM $t");
            $idx = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $idx[] = $row;
                }
                $res->close();
            }
            $out['indexes'][$t] = $idx;
        } catch (Throwable $e) {
            $out['indexes'][$t] = [];
            $out['errors'][] = "SHOW INDEX $t: " . $e->getMessage();
        }
    }

    // Construir consultas similares a get_pedidos.php para EXPLAIN
    $baseSelect = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.notas, p.fecha_creacion, p.prioridad,
                   a.id as area_id, a.nombre_area as area_nombre, p.estado_id, e.nombre_estado,
                   CASE WHEN EXISTS (SELECT 1 FROM mensajes_devolucion md WHERE md.pedido_id = p.id LIMIT 1) THEN 1 ELSE 0 END as tiene_devoluciones
                   FROM pedidos p
                   JOIN areas a ON p.area_id = a.id
                   LEFT JOIN estados_pedido e ON p.estado_id = e.id";

    $queries = [
        'diseno' => $baseSelect . " WHERE p.area_id IN (1,2,3) ORDER BY p.fecha_creacion DESC",
        'mensajeria' => $baseSelect . " WHERE p.area_id IN (11,12,13) ORDER BY p.fecha_creacion DESC"
    ];

    foreach ($queries as $name => $sql) {
        try {
            $res = $conn->query("EXPLAIN $sql");
            $plan = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $plan[] = $row;
                }
                $res->close();
            }
            $out['explain'][$name] = $plan;
        } catch (Throwable $e) {
            $out['errors'][] = "EXPLAIN $name: " . $e->getMessage();
            $out['explain'][$name] = [];
        }
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $out['success'] = false;
    $out['errors'][] = $e->getMessage();
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
}

if (isset($conn) && ($conn instanceof mysqli)) { $conn->close(); }
?>