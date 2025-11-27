<?php
header('Content-Type: application/json');

require_once 'db_connection.php';

session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Validación defensiva de la conexión
if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log('get_pedidos.php - Conexión inválida: $conn es null o no es mysqli');
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Parámetros para filtrar y paginar
$estado_id_str = $_GET['estado_id'] ?? '';
$area_id_str = $_GET['area_id'] ?? '';
$area_id_not_in_str = $_GET['area_id_not_in'] ?? '';
$limit_str = $_GET['limit'] ?? '';
$offset_str = $_GET['offset'] ?? '';
// Filtros de fecha y prioridad
$fecha_desde = $_GET['fecha_desde'] ?? ''; // formato esperado YYYY-MM-DD
$fecha_hasta = $_GET['fecha_hasta'] ?? ''; // formato esperado YYYY-MM-DD
$urgentes_first = isset($_GET['urgentes_first']) ? ($_GET['urgentes_first'] === 'false' ? false : true) : true;
// Filtro de texto libre
$q = $_GET['q'] ?? '';

// Log para depuración y métricas
$__t0 = microtime(true);
error_log("get_pedidos.php - Parámetros recibidos: estado_id=$estado_id_str, area_id=$area_id_str, area_id_not_in=$area_id_not_in_str, limit=$limit_str, offset=$offset_str, urgentes_first=" . ($urgentes_first ? 'true' : 'false') . ", q=" . (strlen($q) ? $q : '')); 

// Construir consulta optimizada: reemplazar subconsultas correlacionadas por LEFT JOINs derivados por área
// SELECT base
$select_base = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.notas, p.fecha_creacion, p.prioridad,
               a.id as area_id, a.nombre_area as area_nombre, p.estado_id, e.nombre_estado,
               CASE WHEN EXISTS (SELECT 1 FROM mensajes_devolucion md WHERE md.pedido_id = p.id LIMIT 1) THEN 1 ELSE 0 END as tiene_devoluciones";

// Mapear áreas a alias y columnas derivadas
$area_alias_map = [
    12 => ['alias' => 'um',  'col' => 'usuario_proceso_mensajeria'],
    2  => ['alias' => 'ud',  'col' => 'usuario_proceso_diseno'],
    15 => ['alias' => 'ui',  'col' => 'usuario_proceso_impresion'],
    8  => ['alias' => 'us',  'col' => 'usuario_proceso_sublimado'],
    5  => ['alias' => 'uc',  'col' => 'usuario_proceso_confeccion'],
    17 => ['alias' => 'ucc', 'col' => 'usuario_proceso_control_calidad'],
];

// Determinar áreas solicitadas explícitamente (si no hay filtro de área, mantenemos todas)
$requested_area_ids = null;
if (!empty($area_id_str)) {
    $tmp = array_filter(array_map('intval', explode(',', $area_id_str)), function($v){ return $v > 0; });
    if (!empty($tmp)) {
        $requested_area_ids = array_values(array_unique($tmp));
    }
}

// Campos derivados por área (dinámicos): si el área no se solicita, devolver NULL para su columna
$select_areas_parts = [];
foreach ($area_alias_map as $areaId => $info) {
    $alias = $info['alias'];
    $col = $info['col'];
    $should_include = ($requested_area_ids === null) || in_array($areaId, $requested_area_ids, true);
    if ($should_include) {
        $select_areas_parts[] = "CASE WHEN p.area_id = $areaId THEN $alias.nombre_completo ELSE NULL END as $col";
    } else {
        $select_areas_parts[] = "NULL as $col";
    }
}
$select_areas = ', ' . implode(",\n               ", $select_areas_parts);

$from_base = " FROM pedidos p
        JOIN areas a ON p.area_id = a.id
        LEFT JOIN estados_pedido e ON p.estado_id = e.id";

// Joins derivados: último usuario por pedido en cada área específica (dinámicos)
$joins_parts = [];
// función helper para join por área
$make_join = function($areaId, $alias) {
    return "
        LEFT JOIN (
            SELECT hh.pedido_id, uu.nombre_completo
            FROM historial_pedidos hh
            JOIN (
                SELECT pedido_id, MAX(fecha_entrada) AS max_fecha
                FROM historial_pedidos
                WHERE area_id = $areaId
                GROUP BY pedido_id
            ) hmax ON hmax.pedido_id = hh.pedido_id AND hmax.max_fecha = hh.fecha_entrada
            JOIN usuarios uu ON uu.id = hh.usuario_entrada_id
            WHERE hh.area_id = $areaId
        ) $alias ON $alias.pedido_id = p.id";
};
foreach ($area_alias_map as $areaId => $info) {
    $alias = $info['alias'];
    $should_include = ($requested_area_ids === null) || in_array($areaId, $requested_area_ids, true);
    if ($should_include) {
        $joins_parts[] = $make_join($areaId, $alias);
    }
}
$joins = "\n" . implode("\n", $joins_parts);

$sql = $select_base . $select_areas . $from_base . $joins;

$params = [];
$types = '';
$conditions = [];

// Filtros por fecha (si se proporcionan)
if (!empty($fecha_desde)) {
    $conditions[] = "p.fecha_creacion >= ?";
    $params[] = $fecha_desde . ' 00:00:00';
    $types .= 's';
}
if (!empty($fecha_hasta)) {
    $conditions[] = "p.fecha_creacion <= ?";
    $params[] = $fecha_hasta . ' 23:59:59';
    $types .= 's';
}

// Filtro por estado_id
if (!empty($estado_id_str)) {
    $estado_ids = explode(',', $estado_id_str);
    if (!empty($estado_ids)) {
        $placeholders = implode(',', array_fill(0, count($estado_ids), '?'));
        $conditions[] = "p.estado_id IN ($placeholders)";
        foreach ($estado_ids as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($estado_ids));
    }
}

// Filtro por area_id
if (!empty($area_id_str)) {
    $area_ids = explode(',', $area_id_str);
    if (!empty($area_ids)) {
        $placeholders = implode(',', array_fill(0, count($area_ids), '?'));
        $conditions[] = "p.area_id IN ($placeholders)";
        foreach ($area_ids as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($area_ids));
    }
}

// Filtro por area_id NOT IN
if (!empty($area_id_not_in_str)) {
    $area_ids_not_in = explode(',', $area_id_not_in_str);
    if (!empty($area_ids_not_in)) {
        $placeholders = implode(',', array_fill(0, count($area_ids_not_in), '?'));
        $conditions[] = "p.area_id NOT IN ($placeholders)";
        foreach ($area_ids_not_in as $id) {
            $params[] = (int)$id;
        }
        $types .= str_repeat('i', count($area_ids_not_in));
    }
}

// Filtro de texto libre (q)
if (!empty($q)) {
    // Normalizar el término de búsqueda a minúsculas y sin acentos
    $q_norm = mb_strtolower($q, 'UTF-8');
    $q_norm = strtr($q_norm, [
        'á'=>'a','Á'=>'a','à'=>'a','À'=>'a','ä'=>'a','Ä'=>'a','â'=>'a','Â'=>'a',
        'é'=>'e','É'=>'e','è'=>'e','È'=>'e','ë'=>'e','Ë'=>'e','ê'=>'e','Ê'=>'e',
        'í'=>'i','Í'=>'i','ì'=>'i','Ì'=>'i','ï'=>'i','Ï'=>'i','î'=>'i','Î'=>'i',
        'ó'=>'o','Ó'=>'o','ò'=>'o','Ò'=>'o','ö'=>'o','Ö'=>'o','ô'=>'o','Ô'=>'o',
        'ú'=>'u','Ú'=>'u','ù'=>'u','Ù'=>'u','ü'=>'u','Ü'=>'u','û'=>'u','Û'=>'u',
        'ñ'=>'n','Ñ'=>'n'
    ]);

    // Expresión SQL para eliminar acentos en campos y comparar en minúsculas
    $unaccent = function($field) {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($field,'á','a'),'Á','a'),'à','a'),'À','a'),'ä','a'),'Ä','a'),'é','e'),'É','e'),'è','e'),'È','e'),'ë','e'),'Ë','e'))" .
               "";
    };
    // Completar el resto de reemplazos para i, o, u, ü y ñ
    $unaccent_full = function($field) use ($unaccent) {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(" . $field . ",'á','a'),'Á','a'),'à','a'),'À','a'),'ä','a'),'Ä','a','é','e'),'É','e'),'è','e'),'È','e'),'ë','e'),'Ë','e','í','i'),'Í','i'),'ì','i'),'Ì','i'),'ï','i'),'Ï','i','ó','o'),'Ó','o'),'ò','o'),'Ò','o'),'ö','o'),'Ö','o','ú','u'),'Ú','u'),'ù','u'),'Ù','u'),'ü','u'),'Ü','u','ñ','n'),'Ñ','n'))))";
    };

    $conditions[] = "(" .
        $unaccent_full('p.nombre_cliente') . " LIKE ? OR " .
        $unaccent_full('p.numero_guia') . " LIKE ? OR " .
        $unaccent_full('p.correo_cliente') . " LIKE ? OR " .
        $unaccent_full('p.numero_cliente') . " LIKE ? OR " .
        $unaccent_full('p.notas') . " LIKE ?" .
    ")";

    $like = '%' . $q_norm . '%';
    // Cinco parámetros por los cinco campos
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sssss';
}

// Agregar condiciones WHERE si existen
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Orden: aprovechar índices según número de áreas solicitadas
$multiple_areas = ($requested_area_ids !== null && count($requested_area_ids) > 1);
if ($urgentes_first) {
    // Usar prioridad_rank (0=alta,1=normal,2=otros);
    // si hay múltiples áreas, anteponer area_id para habilitar orden por índice compuesto
    if ($multiple_areas) {
        $sql .= " ORDER BY p.area_id ASC, p.prioridad_rank ASC, p.fecha_creacion DESC";
    } else {
        $sql .= " ORDER BY p.prioridad_rank ASC, p.fecha_creacion DESC";
    }
} else {
    if ($multiple_areas) {
        $sql .= " ORDER BY p.area_id ASC, p.fecha_creacion DESC";
    } else {
        $sql .= " ORDER BY p.fecha_creacion DESC";
    }
}

// Paginación segura: limitar resultados
$limit = 0;
$offset = 0;
if ($limit_str !== '') {
    $limit = intval($limit_str);
}
if ($offset_str !== '') {
    $offset = intval($offset_str);
}
// Valores por defecto y límites razonables
if ($limit <= 0) { $limit = 50; }
if ($limit > 500) { $limit = 500; }
if ($offset < 0) { $offset = 0; }

$sql .= " LIMIT $limit OFFSET $offset";

// Log de la consulta SQL
error_log("get_pedidos.php - SQL: $sql");
error_log("get_pedidos.php - Parámetros: " . print_r($params, true));

// Preparar consulta con fallback si falla por cambios de esquema
$stmt = null;
try {
    $stmt = $conn->prepare($sql);
    $__t_prepare = microtime(true);
} catch (mysqli_sql_exception $e) {
    error_log("get_pedidos.php - prepare() falló: " . $e->getMessage() . " -> usando consulta simple de fallback");
    // Construir una consulta más sencilla que no dependa de columnas opcionales,
    // pero incluyendo los nombres de área y estado para la UI de recepción.
    $sql = "SELECT 
                p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.notas, p.fecha_creacion, p.prioridad,
                p.area_id, a.nombre_area AS area_nombre,
                p.estado_id, e.nombre_estado,
                CASE WHEN EXISTS (SELECT 1 FROM mensajes_devolucion md WHERE md.pedido_id = p.id LIMIT 1) THEN 1 ELSE 0 END as tiene_devoluciones
            FROM pedidos p
            LEFT JOIN areas a ON p.area_id = a.id
            LEFT JOIN estados_pedido e ON p.estado_id = e.id";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    // Mantener el mismo criterio de orden que arriba en el fallback
    $multiple_areas = ($requested_area_ids !== null && count($requested_area_ids) > 1);
    if ($urgentes_first) {
        if ($multiple_areas) {
            $sql .= " ORDER BY p.area_id ASC, p.prioridad_rank ASC, p.fecha_creacion DESC";
        } else {
            $sql .= " ORDER BY p.prioridad_rank ASC, p.fecha_creacion DESC";
        }
    } else {
        if ($multiple_areas) {
            $sql .= " ORDER BY p.area_id ASC, p.fecha_creacion DESC";
        } else {
            $sql .= " ORDER BY p.fecha_creacion DESC";
        }
    }
    error_log("get_pedidos.php - SQL (fallback): $sql");
    try {
        $stmt = $conn->prepare($sql);
        $__t_prepare = microtime(true);
    } catch (mysqli_sql_exception $e2) {
        $error_msg = 'Error preparando consulta (fallback): ' . $e2->getMessage();
        error_log("get_pedidos.php - " . $error_msg);
        echo json_encode(['error' => $error_msg]);
        if ($conn instanceof mysqli) {
            $conn->close();
        }
        exit;
    }
}

// Verificar que $stmt sea válido antes de usarlo
if (!($stmt instanceof mysqli_stmt)) {
    $error_msg = 'Error interno: statement no inicializado';
    error_log("get_pedidos.php - " . $error_msg);
    echo json_encode(['error' => $error_msg]);
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    exit;
}

if (!empty($params)) {
    try {
        if ($stmt instanceof mysqli_stmt) {
            $stmt->bind_param($types, ...$params);
        } else {
            throw new mysqli_sql_exception('Statement inválido durante bind_param');
        }
    } catch (mysqli_sql_exception $e) {
        $error_msg = 'Error al vincular parámetros: ' . $e->getMessage();
        error_log("get_pedidos.php - " . $error_msg);
        echo json_encode(['error' => $error_msg]);
        if ($stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        if ($conn instanceof mysqli) {
            $conn->close();
        }
        exit;
    }
}

try {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->execute();
        $__t_exec = microtime(true);

        $pedidos = [];

        // Intentar usar get_result() si está disponible (mysqlnd)
        $result = null;
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
        }

        if ($result && $result instanceof mysqli_result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $pedidos[] = $row;
                }
            }
        } else {
            // Fallback: vincular resultados dinámicamente sin mysqlnd
            // Almacenar el resultado para acceder a la metadata
            if ($stmt->store_result()) {
                $meta = $stmt->result_metadata();
                if ($meta) {
                    $fields = $meta->fetch_fields();
                    $row = [];
                    $bindVars = [];
                    foreach ($fields as $field) {
                        // Inicializar cada campo y crear referencia
                        $row[$field->name] = null;
                        $bindVars[] = &$row[$field->name];
                    }
                    // Vincular resultados dinámicamente
                    call_user_func_array([$stmt, 'bind_result'], $bindVars);
                    // Recuperar filas
                    while ($stmt->fetch()) {
                        // Clonar valores actuales (evitar referencias compartidas)
                        $pedidos[] = array_map(function($v) { return $v; }, $row);
                    }
                }
            }
        }

        // Log del resultado
        error_log("get_pedidos.php - Pedidos encontrados: " . count($pedidos));

        $__t_json_start = microtime(true);
        $___json = json_encode($pedidos);
        echo $___json;
        $__t_json_end = microtime(true);

        // Métricas de tiempo detalladas
        error_log(sprintf(
            'get_pedidos.php - métricas: prepare=%.3fs exec=%.3fs total=%.3fs rows=%d',
            (isset($__t_prepare) ? ($__t_prepare - $__t0) : 0),
            (isset($__t_exec) ? ($__t_exec - (isset($__t_prepare) ? $__t_prepare : $__t0)) : 0),
            ($__t_json_end - $__t0),
            count($pedidos)
        ));

        $stmt->close();
    } else {
        throw new mysqli_sql_exception('Statement inválido durante execute');
    }
} catch (mysqli_sql_exception $e) {
    $error_msg = 'Error al ejecutar consulta SQL: ' . $e->getMessage();
    error_log("get_pedidos.php - " . $error_msg);
    echo json_encode(['error' => $error_msg]);
}

if ($conn instanceof mysqli) {
    $conn->close();
}
?>
