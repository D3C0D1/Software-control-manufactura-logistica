<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connection.php';

$response = [
    'success' => true,
    'changes' => [],
    'errors' => []
];

// Validación defensiva de la conexión
try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        // Intentar crear conexión manualmente usando constantes de config
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->query("SET time_zone = '-05:00'");
        } else {
            throw new Exception('Conexión inválida: objeto $conn nulo y constantes de DB no disponibles');
        }
    }
} catch (Throwable $e) {
    $response['success'] = false;
    $response['errors'][] = 'Error de conexión: ' . $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Confirmar tabla historial_pedidos existe
    $res = ($conn instanceof mysqli) ? $conn->query("SHOW TABLES LIKE 'historial_pedidos'") : null;
    if (!$res || $res->num_rows === 0) {
        throw new Exception("La tabla 'historial_pedidos' no existe");
    }
    if ($res) { $res->close(); }

    // Verificar si existe el índice compuesto recomendado
    $idxName = 'idx_hist_ped_area_fecha';
    $sqlStat = "SELECT 1 FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'historial_pedidos' 
                  AND INDEX_NAME = '$idxName'";
    $resStat = ($conn instanceof mysqli) ? $conn->query($sqlStat) : null;
    $exists = $resStat && $resStat->num_rows > 0;
    if ($resStat) { $resStat->close(); }

    if (!$exists) {
        // Crear índice compuesto para acelerar subconsultas:
        // WHERE pedido_id = ? AND area_id = ? ORDER BY fecha_entrada DESC LIMIT 1
        if ($conn instanceof mysqli) {
            $conn->query("CREATE INDEX $idxName ON historial_pedidos (pedido_id, area_id, fecha_entrada)");
        } else {
            throw new Exception('No se pudo crear el índice: conexión inválida');
        }
        $response['changes'][] = "Índice $idxName creado";
    } else {
        $response['changes'][] = "Índice $idxName ya existe";
    }

    // Índice alterno para acelerar GROUP BY por area_id y pedido_id:
    // Subconsultas del tipo: SELECT pedido_id, MAX(fecha_entrada) FROM historial_pedidos
    // WHERE area_id = ? GROUP BY pedido_id
    $idxNameAlt = 'idx_hist_area_ped_fecha';
    $sqlStatAlt = "SELECT 1 FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'historial_pedidos' 
                  AND INDEX_NAME = '$idxNameAlt'";
    $resStatAlt = ($conn instanceof mysqli) ? $conn->query($sqlStatAlt) : null;
    $existsAlt = $resStatAlt && $resStatAlt->num_rows > 0;
    if ($resStatAlt) { $resStatAlt->close(); }

    if (!$existsAlt) {
        if ($conn instanceof mysqli) {
            $conn->query("CREATE INDEX $idxNameAlt ON historial_pedidos (area_id, pedido_id, fecha_entrada)");
        } else {
            throw new Exception('No se pudo crear el índice alterno: conexión inválida');
        }
        $response['changes'][] = "Índice $idxNameAlt creado";
    } else {
        $response['changes'][] = "Índice $idxNameAlt ya existe";
    }

    // Índice para cerrar historial rápidamente: WHERE pedido_id = ? AND fecha_salida IS NULL
    $idxSalida = 'idx_hist_ped_pedido_salida';
    $sqlStatSalida = "SELECT 1 FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'historial_pedidos' 
                  AND INDEX_NAME = '$idxSalida'";
    $resSalida = ($conn instanceof mysqli) ? $conn->query($sqlStatSalida) : null;
    $existsSalida = $resSalida && $resSalida->num_rows > 0;
    if ($resSalida) { $resSalida->close(); }

    if (!$existsSalida) {
        if ($conn instanceof mysqli) {
            $conn->query("CREATE INDEX $idxSalida ON historial_pedidos (pedido_id, fecha_salida)");
        } else {
            throw new Exception('No se pudo crear el índice de salida: conexión inválida');
        }
        $response['changes'][] = "Índice $idxSalida creado";
    } else {
        $response['changes'][] = "Índice $idxSalida ya existe";
    }

    // Índice compuesto recomendado para pedidos: (area_id, estado_id, fecha_creacion)
    $idxName2 = 'idx_pedidos_area_estado_fecha';
    $sqlStat2 = "SELECT 1 FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'pedidos' 
                  AND INDEX_NAME = '$idxName2'";
    $resStat2 = ($conn instanceof mysqli) ? $conn->query($sqlStat2) : null;
    $exists2 = $resStat2 && $resStat2->num_rows > 0;
    if ($resStat2) { $resStat2->close(); }

    if (!$exists2) {
        if ($conn instanceof mysqli) {
            $conn->query("CREATE INDEX $idxName2 ON pedidos (area_id, estado_id, fecha_creacion)");
        } else {
            throw new Exception('No se pudo crear el índice en pedidos: conexión inválida');
        }
        $response['changes'][] = "Índice $idxName2 creado";
    } else {
        $response['changes'][] = "Índice $idxName2 ya existe";
    }

    // Índice para ordenar por fecha dentro del área: (area_id, fecha_creacion)
    $idxName3 = 'idx_pedidos_area_fecha';
    $sqlStat3 = "SELECT 1 FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'pedidos' 
                  AND INDEX_NAME = '$idxName3'";
    $resStat3 = ($conn instanceof mysqli) ? $conn->query($sqlStat3) : null;
    $exists3 = $resStat3 && $resStat3->num_rows > 0;
    if ($resStat3) { $resStat3->close(); }

    if (!$exists3) {
        if ($conn instanceof mysqli) {
            $conn->query("CREATE INDEX $idxName3 ON pedidos (area_id, fecha_creacion)");
        } else {
            throw new Exception('No se pudo crear el índice en pedidos (area,fecha): conexión inválida');
        }
        $response['changes'][] = "Índice $idxName3 creado";
    } else {
        $response['changes'][] = "Índice $idxName3 ya existe";
    }

    // Columna generada para ordenar prioridad sin CASE en runtime
    // prioridad_rank: 0=alta, 1=normal, 2=baja/otros
    $colCheck = null;
    try {
        $resCol = ($conn instanceof mysqli) ? $conn->query("SHOW COLUMNS FROM pedidos LIKE 'prioridad_rank'") : null;
        $colExists = $resCol && $resCol->num_rows > 0;
        if ($resCol) { $resCol->close(); }

        if (!$colExists) {
            if ($conn instanceof mysqli) {
                $conn->query("ALTER TABLE pedidos ADD COLUMN prioridad_rank TINYINT GENERATED ALWAYS AS (CASE WHEN prioridad='alta' THEN 0 WHEN prioridad='normal' THEN 1 ELSE 2 END) STORED");
                $response['changes'][] = "Columna generada prioridad_rank creada";
            } else {
                throw new Exception('No se pudo crear columna generada: conexión inválida');
            }
        } else {
            $response['changes'][] = "Columna generada prioridad_rank ya existe";
        }
    } catch (Throwable $e) {
        $response['errors'][] = "Error creando columna prioridad_rank: " . $e->getMessage();
    }

    // Índice compuesto (area_id, prioridad_rank, fecha_creacion)
    $idxPrior = 'idx_pedidos_area_prioridad_fecha';
    $sqlIdxPrior = "SELECT 1 FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'pedidos' 
                      AND INDEX_NAME = '$idxPrior'";
    $resIdxPrior = ($conn instanceof mysqli) ? $conn->query($sqlIdxPrior) : null;
    $existsIdxPrior = $resIdxPrior && $resIdxPrior->num_rows > 0;
    if ($resIdxPrior) { $resIdxPrior->close(); }

    if (!$existsIdxPrior) {
        try {
            if ($conn instanceof mysqli) {
                $conn->query("CREATE INDEX $idxPrior ON pedidos (area_id, prioridad_rank, fecha_creacion)");
                $response['changes'][] = "Índice $idxPrior creado";
            } else {
                throw new Exception('No se pudo crear índice prioridad_rank: conexión inválida');
            }
        } catch (Throwable $e) {
            $response['errors'][] = "Error creando índice $idxPrior: " . $e->getMessage();
        }
    } else {
        $response['changes'][] = "Índice $idxPrior ya existe";
    }

    // Índice para acelerar EXISTS en mensajes_devolucion (pedido_id)
    // Este índice evita subconsultas dependientes tipo ALL sobre mensajes_devolucion
    $idxName3 = 'idx_md_pedido';
    // Verificar que la tabla exista
    $resMdTbl = ($conn instanceof mysqli) ? $conn->query("SHOW TABLES LIKE 'mensajes_devolucion'") : null;
    if ($resMdTbl && $resMdTbl->num_rows > 0) {
        $resMdTbl->close();

        // Comprobar si el índice ya existe
        $sqlStat3 = "SELECT 1 FROM information_schema.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'mensajes_devolucion' 
                       AND INDEX_NAME = '$idxName3'";
        $resStat3 = ($conn instanceof mysqli) ? $conn->query($sqlStat3) : null;
        $exists3 = $resStat3 && $resStat3->num_rows > 0;
        if ($resStat3) { $resStat3->close(); }

        if (!$exists3) {
            if ($conn instanceof mysqli) {
                $conn->query("CREATE INDEX $idxName3 ON mensajes_devolucion (pedido_id)");
            } else {
                throw new Exception('No se pudo crear el índice en mensajes_devolucion: conexión inválida');
            }
            $response['changes'][] = "Índice $idxName3 creado";
        } else {
            $response['changes'][] = "Índice $idxName3 ya existe";
        }
    } else {
        if ($resMdTbl) { $resMdTbl->close(); }
        $response['errors'][] = "Tabla mensajes_devolucion no existe; no se crea índice $idxName3";
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $response['success'] = false;
    $response['errors'][] = $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

if ($conn instanceof mysqli) {
    $conn->close();
}
?>