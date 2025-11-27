<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Conexión a la base de datos no disponible.');
    }

    // Acepta POST (JSON o x-www-form-urlencoded) y GET para conveniencia
    $raw = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) { $raw = $json; }
        }
        if (empty($raw)) {
            $raw = $_POST;
        }
    } else {
        $raw = $_GET;
    }

    // Param guias: puede ser coma-separada o arreglo
    $guiasParam = $raw['guias'] ?? $raw['numero_guia'] ?? null;
    if ($guiasParam === null) {
        throw new Exception('Proporcione el parámetro "guias" (coma-separado) o "numero_guia".');
    }

    $guias = [];
    if (is_array($guiasParam)) {
        $guias = $guiasParam;
    } else {
        $guias = array_filter(array_map('trim', explode(',', (string)$guiasParam)), function($g) { return $g !== ''; });
    }
    if (empty($guias)) {
        throw new Exception('No se proporcionaron guías válidas.');
    }

    $results = [];

    foreach ($guias as $guia) {
        try {
            // Iniciar transacción por cada pedido
            $conn->autocommit(false);

            // Buscar ID por numero_guia
            $stmt_find = $conn->prepare('SELECT id FROM pedidos WHERE numero_guia = ? LIMIT 1');
            if (!$stmt_find) { throw new Exception('No se pudo preparar búsqueda por guía.'); }
            $stmt_find->bind_param('s', $guia);
            $stmt_find->execute();
            $stmt_find->bind_result($pedido_id);
            $found = $stmt_find->fetch();
            $stmt_find->close();

            if (!$found) {
                throw new Exception('No se encontró pedido con esa guía.');
            }

            // Eliminar mensajes_devolucion (si existe la tabla)
            try {
                $stmt_md = $conn->prepare('DELETE FROM mensajes_devolucion WHERE pedido_id = ?');
                if ($stmt_md) {
                    $stmt_md->bind_param('i', $pedido_id);
                    $stmt_md->execute();
                    $stmt_md->close();
                }
            } catch (mysqli_sql_exception $ex) {
                if ($ex->getCode() === 1146) {
                    error_log('delete_pedidos_por_guia.php - Tabla mensajes_devolucion no existe, omitiendo.');
                } else {
                    throw $ex;
                }
            }

            // Eliminar archivos adjuntos físicos (pedido_adjuntos)
            $stmt_adj = $conn->prepare('SELECT ruta_archivo FROM pedido_adjuntos WHERE pedido_id = ?');
            if ($stmt_adj) {
                $stmt_adj->bind_param('i', $pedido_id);
                $stmt_adj->execute();
                $stmt_adj->bind_result($ruta_archivo);
                while ($stmt_adj->fetch()) {
                    $archivo_path = '../' . $ruta_archivo;
                    if (is_string($ruta_archivo) && $ruta_archivo !== '' && file_exists($archivo_path)) {
                        @unlink($archivo_path);
                    }
                }
                $stmt_adj->close();
            }

            // Eliminar adjuntos en DB
            $stmt_del_adj = $conn->prepare('DELETE FROM pedido_adjuntos WHERE pedido_id = ?');
            if ($stmt_del_adj) {
                $stmt_del_adj->bind_param('i', $pedido_id);
                $stmt_del_adj->execute();
                $stmt_del_adj->close();
            }

            // Eliminar archivo_adjunto legacy si existe en pedidos
            $stmt_legacy = $conn->prepare('SELECT archivo_adjunto FROM pedidos WHERE id = ? AND archivo_adjunto IS NOT NULL');
            if ($stmt_legacy) {
                $stmt_legacy->bind_param('i', $pedido_id);
                $stmt_legacy->execute();
                $stmt_legacy->bind_result($archivo_legacy);
                if ($stmt_legacy->fetch()) {
                    $archivo_path = '../' . $archivo_legacy;
                    if (is_string($archivo_legacy) && $archivo_legacy !== '' && file_exists($archivo_path)) {
                        @unlink($archivo_path);
                    }
                }
                $stmt_legacy->close();
            }

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
                // Obtener datos del pedido (nombre, teléfono) para el log
                $nombre_cliente = null; $numero_cliente = null;
                $stmt_info = $conn->prepare('SELECT nombre_cliente, numero_cliente FROM pedidos WHERE id = ? LIMIT 1');
                if ($stmt_info) {
                    $stmt_info->bind_param('i', $pedido_id);
                    if ($stmt_info->execute()) {
                        $stmt_info->bind_result($nombre_cliente, $numero_cliente);
                        $stmt_info->fetch();
                    }
                    $stmt_info->close();
                }

                $stmt_log = $conn->prepare('INSERT INTO pedidos_eliminados (pedido_id, numero_guia, nombre_cliente, numero_cliente, usuario_id, fecha_eliminacion) VALUES (?, ?, ?, ?, ?, NOW())');
                if ($stmt_log) {
                    $stmt_log->bind_param('isssi', $pedido_id, $guia, $nombre_cliente, $numero_cliente, $usuario_id);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            } catch (mysqli_sql_exception $ex) {
                error_log('delete_pedidos_por_guia.php - Auditoría de eliminación falló: ' . $ex->getMessage());
            }

            // Eliminar pedido
            $stmt_del_pedido = $conn->prepare('DELETE FROM pedidos WHERE id = ?');
            if (!$stmt_del_pedido) { throw new Exception('No se pudo preparar eliminación del pedido.'); }
            $stmt_del_pedido->bind_param('i', $pedido_id);
            if (!$stmt_del_pedido->execute()) {
                throw new Exception('Error al eliminar el pedido: ' . $stmt_del_pedido->error);
            }
            if ($stmt_del_pedido->affected_rows <= 0) {
                throw new Exception('No se eliminó el pedido (no afectó filas).');
            }
            $stmt_del_pedido->close();

            // Confirmar
            $conn->commit();
            $conn->autocommit(true);

            $results[] = [
                'numero_guia' => $guia,
                'pedido_id' => $pedido_id,
                'success' => true,
                'message' => 'Pedido eliminado exitosamente'
            ];

        } catch (Exception $e) {
            // Revertir por pedido
            if ($conn instanceof mysqli) { $conn->rollback(); $conn->autocommit(true); }
            $results[] = [
                'numero_guia' => $guia,
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->close();
    }
}
?>