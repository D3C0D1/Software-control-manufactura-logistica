<?php
// Forzar salida JSON desde db_connection si la conexión falla
if (!defined('RETURN_JSON')) {
    define('RETURN_JSON', true);
}
require_once 'db_connection.php';
session_start();
header('Content-Type: application/json');

// Agregar logging para depuración
error_log("update_pedido_area.php - Inicio de ejecución");
error_log("POST data: " . print_r($_POST, true));

// Validación defensiva de la conexión
if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log("update_pedido_area.php - Conexión a BD inválida (conn es null o no es mysqli)");
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    error_log("update_pedido_area.php - Usuario no autorizado");
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Capturar campos desde POST (FormData tradicional)
$pedido_id = $_POST['id'] ?? $_POST['pedido_id'] ?? null;
$new_area_id = $_POST['area_id'] ?? $_POST['new_area_id'] ?? null;
$new_estado_id = $_POST['estado_id'] ?? $_POST['new_estado_id'] ?? null;
// Aceptar ambos nombres de parámetro para mensajes de devolución
$mensaje_devolucion = $_POST['mensaje_devolucion'] ?? ($_POST['mensaje'] ?? null);

// Fallback: intentar leer JSON si $_POST llega vacío en algunos hostings
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if ((!$pedido_id || !$new_area_id || !$new_estado_id)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            // Aceptar claves alternativas
            $pedido_id = $pedido_id ?: ($json['id'] ?? $json['pedido_id'] ?? null);
            $new_area_id = $new_area_id ?: ($json['area_id'] ?? $json['new_area_id'] ?? null);
            $new_estado_id = $new_estado_id ?: ($json['estado_id'] ?? $json['new_estado_id'] ?? null);
            $mensaje_devolucion = $mensaje_devolucion ?: ($json['mensaje_devolucion'] ?? null);
            error_log("update_pedido_area.php - Cuerpo JSON detectado y mapeado (CT=$content_type, len=" . strlen($raw) . ")");
        } else {
            error_log("update_pedido_area.php - Cuerpo no-JSON o JSON inválido (CT=$content_type, len=" . strlen($raw) . ")");
        }
    } else {
        error_log("update_pedido_area.php - php://input vacío (CT=$content_type)");
    }
}

// Normalizar a enteros si son numéricos
if (is_string($pedido_id) && ctype_digit($pedido_id)) $pedido_id = (int)$pedido_id;
if (is_string($new_area_id) && ctype_digit($new_area_id)) $new_area_id = (int)$new_area_id;
if (is_string($new_estado_id) && ctype_digit($new_estado_id)) $new_estado_id = (int)$new_estado_id;

error_log("update_pedido_area.php - Parámetros recibidos normalizados: pedido_id=" . var_export($pedido_id, true) . ", new_area_id=" . var_export($new_area_id, true) . ", new_estado_id=" . var_export($new_estado_id, true) . ", mensaje_devolucion=" . var_export($mensaje_devolucion, true));

if ($pedido_id === null || $new_area_id === null || $new_estado_id === null) {
    $faltantes = [];
    if ($pedido_id === null) $faltantes[] = 'id/pedido_id';
    if ($new_area_id === null) $faltantes[] = 'area_id/new_area_id';
    if ($new_estado_id === null) $faltantes[] = 'estado_id/new_estado_id';
    error_log("update_pedido_area.php - Datos incompletos: faltan " . implode(', ', $faltantes));
    echo json_encode(['success' => false, 'message' => 'Datos incompletos: faltan ' . implode(', ', $faltantes)]);
    exit;
}

// Iniciar transacción de forma segura
if (!$conn->begin_transaction()) {
    error_log("update_pedido_area.php - No se pudo iniciar transacción: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'No se pudo iniciar transacción.']);
    exit;
}

try {
    // Verificar que el pedido existe
    $sql_check = "SELECT * FROM pedidos WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check || !($stmt_check instanceof mysqli_stmt)) {
        error_log("update_pedido_area.php - Error al preparar verificación de pedido: " . $conn->error);
        throw new Exception('Error al preparar verificación de pedido.');
    }
    $stmt_check->bind_param('i', $pedido_id);
    if (!$stmt_check->execute()) {
        error_log("update_pedido_area.php - Error al ejecutar verificación de pedido: " . $stmt_check->error);
        throw new Exception('Error al ejecutar verificación de pedido: ' . $stmt_check->error);
    }
// Obtener datos del pedido sin depender de get_result
$pedido_actual = null;
if (method_exists($stmt_check, 'get_result')) {
    $result = $stmt_check->get_result();
    if ($result && ($result instanceof mysqli_result) && $result->num_rows > 0) {
        $pedido_actual = $result->fetch_assoc();
    }
} else {
    // Fallback: bind_result
    $stmt_check->store_result();
    // Obtener metadatos para saber columnas
    $meta = $stmt_check->result_metadata();
    if ($meta) {
        $fields = $meta->fetch_fields();
        $row = [];
        $bindVars = [];
        foreach ($fields as $field) {
            $row[$field->name] = null;
            $bindVars[] = &$row[$field->name];
        }
        call_user_func_array([$stmt_check, 'bind_result'], $bindVars);
        if ($stmt_check->num_rows > 0 && $stmt_check->fetch()) {
            // Clonar valores
            $pedido_actual = array_map(function($v) { return $v; }, $row);
        }
    }
}

if (!$pedido_actual) {
    error_log("update_pedido_area.php - Pedido no encontrado: $pedido_id");
    echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
    exit;
}
    error_log("update_pedido_area.php - Pedido actual: área=" . $pedido_actual['area_id'] . ", estado=" . $pedido_actual['estado_id']);
    
    // Detectar si es una devolución (retroceso en el flujo)
    $es_devolucion = false;
    $area_actual = $pedido_actual['area_id'];

    // Nombres de áreas (solo para mensajes informativos)
    $flujo_areas = [
        1 => 'Recepción',
        2 => 'Diseño', 
        3 => 'Preparado de Diseño',
        4 => 'Recepción de Confección',
        5 => 'En Proceso de Confección',
        6 => 'Preparado de Confección',
        7 => 'Recepción de Sublimado',
        8 => 'En Proceso de Sublimado', 
        9 => 'Preparado de Sublimado',
        11 => 'Recepción de Mensajería',
        12 => 'En Proceso de Mensajería',
        13 => 'Preparado de Mensajería',
        14 => 'En Proceso de Impresión',
        15 => 'Preparado de Impresión',
        17 => 'Control de Calidad Final',
        10 => 'Finalizado'
    ];

    // Detectar devolución: casos específicos de retroceso.
    // Regla 1: movimientos dentro de Mensajería (11 -> 12 -> 13) NO son devolución.
    // Regla 2: movimientos dentro de Control de Calidad (17 -> 18 -> 19) NO son devolución.
    // Regla 3: solo consideramos devoluciones automáticas cuando hay un retroceso desde un estado "Preparado"
    //          hacia áreas iniciales de otros flujos (Diseño/Confección/Sublimado/Impresión/Mensajería),
    //          para evitar falsos positivos.
    $movimiento_mensajeria = in_array($area_actual, [11,12,13]) && in_array($new_area_id, [11,12,13]);
    $movimiento_cc = in_array($area_actual, [17,18,19]) && in_array($new_area_id, [17,18,19]);

    if (!$movimiento_mensajeria && !$movimiento_cc) {
        if (($area_actual == 3 && in_array($new_area_id, [1, 2])) || // Desde Preparado Diseño a Recepción/Diseño
            ($area_actual == 6 && in_array($new_area_id, [1, 2, 4, 5])) || // Desde Preparado Confección
            ($area_actual == 9 && in_array($new_area_id, [1, 2, 7, 8])) || // Desde Preparado Sublimado
            ($area_actual == 15 && in_array($new_area_id, [1, 2, 13, 14])) || // Desde Preparado Impresión
            ($area_actual == 12 && in_array($new_area_id, [11, 1]))) { // Mensajería: retroceso a Recepción Mensajería o Recepción general
            $es_devolucion = true;
            error_log("update_pedido_area.php - Devolución automática detectada: área $area_actual -> $new_area_id");
        }
    }
    
    // Cerrar el registro anterior en historial_pedidos (si existe uno abierto)
    $sql_close_previous = "UPDATE historial_pedidos SET fecha_salida = NOW(), tiempo_en_area = TIMESTAMPDIFF(SECOND, fecha_entrada, NOW()) WHERE pedido_id = ? AND fecha_salida IS NULL";
    $stmt_close = $conn->prepare($sql_close_previous);
    if (!$stmt_close || !($stmt_close instanceof mysqli_stmt)) {
        error_log("update_pedido_area.php - Error al preparar cierre de historial: " . $conn->error);
        throw new Exception('Error al preparar cierre de historial.');
    }
    $stmt_close->bind_param('i', $pedido_id);
    if (!$stmt_close->execute()) {
        error_log("update_pedido_area.php - Error al cerrar historial previo: " . $stmt_close->error);
        throw new Exception('Error al cerrar historial previo: ' . $stmt_close->error);
    }
    
    // Actualizar el área y el estado del pedido
    $sql_update = "UPDATE pedidos SET area_id = ?, estado_id = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update || !($stmt_update instanceof mysqli_stmt)) {
        error_log("update_pedido_area.php - Error al preparar UPDATE de pedido: " . $conn->error);
        throw new Exception('Error al preparar actualización de pedido.');
    }
    $stmt_update->bind_param('iii', $new_area_id, $new_estado_id, $pedido_id);
    $result_update = $stmt_update->execute();
    
    if (!$result_update) {
        error_log("update_pedido_area.php - Error en UPDATE: " . $stmt_update->error);
        throw new Exception("Error al actualizar el pedido: " . $stmt_update->error);
    }
    
    error_log("update_pedido_area.php - Filas afectadas: " . $stmt_update->affected_rows);

    // Registrar en el historial con la estructura correcta
    $id_usuario = $_SESSION['user_id'];
    error_log("update_pedido_area.php - ID de usuario en sesión: $id_usuario");
    
    // Verificar que el usuario existe en la tabla usuarios
    $sql_check_user = "SELECT id, usuario, nombre_completo FROM usuarios WHERE id = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    if (!$stmt_check_user || !($stmt_check_user instanceof mysqli_stmt)) {
        error_log("update_pedido_area.php - Error al preparar verificación de usuario: " . $conn->error);
        throw new Exception('Error al preparar verificación de usuario.');
    }
    $stmt_check_user->bind_param('i', $id_usuario);
    if (!$stmt_check_user->execute()) {
        error_log("update_pedido_area.php - Error al ejecutar verificación de usuario: " . $stmt_check_user->error);
        throw new Exception('Error al ejecutar verificación de usuario: ' . $stmt_check_user->error);
    }
// Obtener datos del usuario sin depender de get_result
$usuario_data = null;
if (method_exists($stmt_check_user, 'get_result')) {
    $result_check_user = $stmt_check_user->get_result();
    if ($result_check_user && ($result_check_user instanceof mysqli_result) && $result_check_user->num_rows > 0) {
        $usuario_data = $result_check_user->fetch_assoc();
    }
} else {
    $stmt_check_user->store_result();
    $meta2 = $stmt_check_user->result_metadata();
    if ($meta2) {
        $fields2 = $meta2->fetch_fields();
        $row2 = [];
        $bindVars2 = [];
        foreach ($fields2 as $field) {
            $row2[$field->name] = null;
            $bindVars2[] = &$row2[$field->name];
        }
        call_user_func_array([$stmt_check_user, 'bind_result'], $bindVars2);
        if ($stmt_check_user->num_rows > 0 && $stmt_check_user->fetch()) {
            $usuario_data = array_map(function($v) { return $v; }, $row2);
        }
    }
}

if (!$usuario_data) {
    error_log("update_pedido_area.php - Usuario no existe en tabla usuarios: $id_usuario");
    throw new Exception("Error: Usuario no válido en sesión (ID: $id_usuario)");
} else {
    error_log("update_pedido_area.php - Usuario encontrado: " . print_r($usuario_data, true));
}
    
    $sql_historial = "INSERT INTO historial_pedidos (pedido_id, area_id, usuario_entrada_id, fecha_entrada) VALUES (?, ?, ?, NOW())";
    $stmt_historial = $conn->prepare($sql_historial);
    if (!$stmt_historial || !($stmt_historial instanceof mysqli_stmt)) {
        error_log("update_pedido_area.php - Error al preparar inserción de historial: " . $conn->error);
        throw new Exception('Error al preparar inserción de historial.');
    }
    $stmt_historial->bind_param('iii', $pedido_id, $new_area_id, $id_usuario);
    
    try {
        $result_historial = $stmt_historial->execute();
        if (!$result_historial) {
            throw new mysqli_sql_exception($stmt_historial->error);
        }
    } catch (mysqli_sql_exception $e) {
        // Fallback si historial_pedidos.id no es AUTO_INCREMENT
        if (strpos($e->getMessage(), "Field 'id' doesn't have a default value") !== false) {
            error_log('update_pedido_area.php - historial_pedidos requiere ID manual, aplicando fallback: ' . $e->getMessage());
            $next_id = 1;
            try {
                $res_max = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM historial_pedidos");
                if ($res_max) {
                    $row = $res_max->fetch_assoc();
                    if ($row && isset($row['next_id'])) {
                        $next_id = (int)$row['next_id'];
                    }
                    $res_max->close();
                }
            } catch (mysqli_sql_exception $e2) {
                error_log('update_pedido_area.php - Error calculando next_id de historial_pedidos: ' . $e2->getMessage());
            }

            $sql_historial2 = "INSERT INTO historial_pedidos (id, pedido_id, area_id, usuario_entrada_id, fecha_entrada) VALUES (?, ?, ?, ?, NOW())";
            $stmt_historial2 = $conn->prepare($sql_historial2);
            if ($stmt_historial2) {
                $stmt_historial2->bind_param('iiii', $next_id, $pedido_id, $new_area_id, $id_usuario);
                if (!$stmt_historial2->execute()) {
                    error_log('update_pedido_area.php - Fallback historial falló: ' . $stmt_historial2->error);
                    throw new Exception('Error al registrar historial (fallback): ' . $stmt_historial2->error);
                }
                $stmt_historial2->close();
            } else {
                error_log('update_pedido_area.php - No se pudo preparar inserción de historial con ID: ' . $conn->error);
                throw new Exception('Error al preparar historial con ID: ' . $conn->error);
            }
        } else {
            error_log("update_pedido_area.php - Error en historial: " . $e->getMessage());
            throw new Exception("Error al registrar en historial: " . $e->getMessage());
        }
    }

    // Si hay mensaje explícito de devolución o se detectó una devolución automática, guardarlo en la tabla
    // Nota: Para evitar falsos positivos, priorizamos el mensaje explícito del usuario.
    if (!empty($mensaje_devolucion) || $es_devolucion) {
        // Crear tabla si no existe
        $sql_create_table = "CREATE TABLE IF NOT EXISTS mensajes_devolucion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            usuario_id INT NOT NULL,
            area_origen INT NOT NULL,
            area_destino INT NOT NULL,
            mensaje TEXT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )";
        if (!$conn->query($sql_create_table)) {
            error_log("update_pedido_area.php - Error al crear tabla mensajes_devolucion: " . $conn->error);
            throw new Exception('Error al validar/crear tabla mensajes_devolucion: ' . $conn->error);
        }
        
        // Obtener el nombre del usuario para insertar
        $usuario_nombre = $usuario_data['nombre_completo'];
        
        // Preparar mensaje de devolución: si el usuario no especifica, usar uno automático descriptivo
        $mensaje_final = !empty($mensaje_devolucion)
            ? $mensaje_devolucion
            : "Devolución automática detectada: pedido movido desde " . ($flujo_areas[$area_actual] ?? ("Área $area_actual")) . " a " . ($flujo_areas[$new_area_id] ?? ("Área $new_area_id"));
        
        // Insertar mensaje de devolución (incluir área_destino para cumplir NOT NULL)
        $sql_mensaje = "INSERT INTO mensajes_devolucion (pedido_id, usuario_id, area_origen, area_destino, mensaje) VALUES (?, ?, ?, ?, ?)";
        $stmt_mensaje = $conn->prepare($sql_mensaje);
        if (!$stmt_mensaje || !($stmt_mensaje instanceof mysqli_stmt)) {
            error_log("update_pedido_area.php - Error al preparar inserción de mensaje: " . $conn->error);
            throw new Exception('Error al preparar inserción de mensaje.');
        }
        $stmt_mensaje->bind_param('iiiis', $pedido_id, $id_usuario, $pedido_actual['area_id'], $new_area_id, $mensaje_final);
        $result_mensaje = $stmt_mensaje->execute();
        
        if (!$result_mensaje) {
            error_log("update_pedido_area.php - Error al guardar mensaje: " . $stmt_mensaje->error);
            throw new Exception("Error al guardar mensaje de devolución: " . $stmt_mensaje->error);
        }
        
        error_log("update_pedido_area.php - Mensaje de devolución guardado");
    }

    $conn->commit();
    error_log("update_pedido_area.php - Transacción exitosa");
    echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("update_pedido_area.php - Error en transacción: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}

if (isset($stmt_check) && ($stmt_check instanceof mysqli_stmt)) $stmt_check->close();
if (isset($stmt_close) && ($stmt_close instanceof mysqli_stmt)) $stmt_close->close();
if (isset($stmt_update) && ($stmt_update instanceof mysqli_stmt)) $stmt_update->close();
if (isset($stmt_check_user) && ($stmt_check_user instanceof mysqli_stmt)) $stmt_check_user->close();
if (isset($stmt_historial) && ($stmt_historial instanceof mysqli_stmt)) $stmt_historial->close();
if (isset($stmt_mensaje) && ($stmt_mensaje instanceof mysqli_stmt)) $stmt_mensaje->close();
$conn->close();
?>