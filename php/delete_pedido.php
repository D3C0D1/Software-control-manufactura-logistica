<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    /** @var mysqli $conn */
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Conexión a la base de datos no disponible.');
    }
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    // Obtener el ID del pedido (POST preferente, fallback a JSON y GET)
    $idRaw = $_POST['id'] ?? $_POST['pedido_id'] ?? null;
    if ($idRaw === null) {
        $input = file_get_contents('php://input');
        if (is_string($input) && strlen(trim($input)) > 0) {
            $json = json_decode($input, true);
            if (is_array($json)) {
                $idRaw = $json['id'] ?? $json['pedido_id'] ?? null;
            }
        }
    }
    if ($idRaw === null) {
        $idRaw = $_GET['id'] ?? $_GET['pedido_id'] ?? null;
    }
    // Normalizar ID si viene como string con espacios o caracteres
    if (is_string($idRaw)) {
        $idRaw = trim($idRaw);
        // Si es dígito puro, ok; si no, extraer dígitos
        if (!ctype_digit($idRaw)) {
            $digits = preg_replace('/[^0-9]/', '', $idRaw);
            $idRaw = ($digits !== '') ? $digits : $idRaw;
        }
    }
    error_log('delete_pedido.php - ID recibido normalizado: ' . var_export($idRaw, true));

    // Validar entero positivo
    $id = filter_var($idRaw, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($id === false || $id === null) {
        throw new Exception('ID de pedido inválido.');
    }

    // Iniciar transacción
    if ($conn instanceof mysqli) {
        $conn->autocommit(false);
    }

    // Obtener datos del pedido antes de eliminar (para registrar auditoría de eliminación)
    $numero_guia = null;
    $nombre_cliente = null;
    $numero_cliente = null;
    $stmt_info = $conn->prepare("SELECT numero_guia, nombre_cliente, numero_cliente FROM pedidos WHERE id = ? LIMIT 1");
    if ($stmt_info instanceof mysqli_stmt) {
        $stmt_info->bind_param("i", $id);
        if ($stmt_info->execute()) {
            $stmt_info->bind_result($numero_guia, $nombre_cliente, $numero_cliente);
            $stmt_info->fetch();
        }
        $stmt_info->close();
    }

    // NUEVO: Eliminar mensajes de devolución asociados al pedido (ignorar si la tabla no existe)
    try {
        $sql_delete_mensajes = "DELETE FROM mensajes_devolucion WHERE pedido_id = ?";
        $stmt_delete_mensajes = $conn->prepare($sql_delete_mensajes);
        if ($stmt_delete_mensajes instanceof mysqli_stmt) {
            $stmt_delete_mensajes->bind_param("i", $id);
            $stmt_delete_mensajes->execute();
            $stmt_delete_mensajes->close();
        }
    } catch (mysqli_sql_exception $ex) {
        // Código 1146: Table doesn't exist (ER_NO_SUCH_TABLE)
        if ($ex->getCode() === 1146) {
            error_log('delete_pedido.php - Tabla mensajes_devolucion no existe, omitiendo eliminación.');
        } else {
            throw $ex; // Re-lanzar otras excepciones
        }
    }

    // Primero, obtener información de archivos adjuntos para eliminarlos (sin usar get_result)
    $sql_adjuntos = "SELECT ruta_archivo FROM pedido_adjuntos WHERE pedido_id = ?";
    $stmt_adjuntos = $conn->prepare($sql_adjuntos);
    if ($stmt_adjuntos instanceof mysqli_stmt) {
        $stmt_adjuntos->bind_param("i", $id);
        $stmt_adjuntos->execute();
        $stmt_adjuntos->bind_result($ruta_archivo);

        // Eliminar archivos físicos
        while ($stmt_adjuntos->fetch()) {
            $archivo_path = '../' . $ruta_archivo;
            if (is_string($ruta_archivo) && $ruta_archivo !== '' && file_exists($archivo_path)) {
                @unlink($archivo_path);
            }
        }
        $stmt_adjuntos->close();
    }

    // Eliminar adjuntos de la base de datos
    $sql_delete_adjuntos = "DELETE FROM pedido_adjuntos WHERE pedido_id = ?";
    $stmt_delete_adjuntos = $conn->prepare($sql_delete_adjuntos);
    if ($stmt_delete_adjuntos instanceof mysqli_stmt) {
        $stmt_delete_adjuntos->bind_param("i", $id);
        $stmt_delete_adjuntos->execute();
        $stmt_delete_adjuntos->close();
    }

    // También verificar si hay archivo_adjunto en la tabla pedidos (campo legacy) sin get_result
    $sql_archivo_legacy = "SELECT archivo_adjunto FROM pedidos WHERE id = ? AND archivo_adjunto IS NOT NULL";
    $stmt_archivo_legacy = $conn->prepare($sql_archivo_legacy);
    if ($stmt_archivo_legacy instanceof mysqli_stmt) {
        $stmt_archivo_legacy->bind_param("i", $id);
        $stmt_archivo_legacy->execute();
        $stmt_archivo_legacy->bind_result($archivo_adjunto_legacy);

        if ($stmt_archivo_legacy->fetch()) {
            $archivo_path = '../' . $archivo_adjunto_legacy;
            if (is_string($archivo_adjunto_legacy) && $archivo_adjunto_legacy !== '' && file_exists($archivo_path)) {
                @unlink($archivo_path);
            }
        }
        $stmt_archivo_legacy->close();
    }

    // Eliminar el pedido
    $sql_delete_pedido = "DELETE FROM pedidos WHERE id = ?";
    $stmt_delete_pedido = $conn->prepare($sql_delete_pedido);
    if ($stmt_delete_pedido instanceof mysqli_stmt) {
        $stmt_delete_pedido->bind_param("i", $id);

        // Registrar auditoría de eliminación antes de borrar definitivamente
        try {
            // Crear tabla de auditoría de eliminaciones si no existe
            $conn->query("CREATE TABLE IF NOT EXISTS pedidos_eliminados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                numero_guia VARCHAR(255),
                nombre_cliente VARCHAR(255),
                numero_cliente VARCHAR(50),
                usuario_id INT NULL,
                fecha_eliminacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $usuario_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $stmt_log = $conn->prepare("INSERT INTO pedidos_eliminados (pedido_id, numero_guia, nombre_cliente, numero_cliente, usuario_id, fecha_eliminacion) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt_log instanceof mysqli_stmt) {
                $stmt_log->bind_param("isssi", $id, $numero_guia, $nombre_cliente, $numero_cliente, $usuario_id);
                $stmt_log->execute();
                $stmt_log->close();
            }
        } catch (mysqli_sql_exception $e) {
            // Si falla la auditoría, continuar con la eliminación para no bloquear operación
            error_log('delete_pedido.php - Auditoría de eliminación falló: ' . $e->getMessage());
        }

        if ($stmt_delete_pedido->execute()) {
            if ($stmt_delete_pedido->affected_rows > 0) {
                // Confirmar transacción
                if ($conn instanceof mysqli) {
                    $conn->commit();
                }
                echo json_encode(['success' => true, 'message' => 'Pedido eliminado exitosamente.']);
            } else {
                throw new Exception('No se encontró el pedido especificado.');
            }
        } else {
            throw new Exception('Error al eliminar el pedido: ' . $stmt_delete_pedido->error);
        }

        $stmt_delete_pedido->close();
    } else {
        throw new Exception('Error al preparar la consulta: ' . ($conn instanceof mysqli ? $conn->error : 'Conexión no disponible'));
    }

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Restaurar autocommit y cerrar conexión
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->autocommit(true);
        $conn->close();
    }
}
?>