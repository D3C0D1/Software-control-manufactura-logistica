<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id = $_POST['id'] ?? '';
$nombre_cliente = trim($_POST['nombre_completo'] ?? '');
$email_cliente = trim($_POST['email'] ?? '');
$movil_cliente = trim($_POST['movil'] ?? '');
$notas = trim($_POST['notas'] ?? '');
$delete_adjuntos = isset($_POST['delete_adjuntos']) ? json_decode($_POST['delete_adjuntos'], true) : [];

if (empty($id) || empty($nombre_cliente) || empty($email_cliente) || empty($movil_cliente)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, complete todos los campos obligatorios.']);
    exit;
}

if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Eliminar adjuntos seleccionados
    if (!empty($delete_adjuntos)) {
        $sql_get_adjunto = "SELECT ruta_archivo FROM pedido_adjuntos WHERE id = ? AND pedido_id = ?";
        $stmt_get_adjunto = $conn->prepare($sql_get_adjunto);

        $sql_delete_adjunto = "DELETE FROM pedido_adjuntos WHERE id = ? AND pedido_id = ?";
        $stmt_delete_adjunto = $conn->prepare($sql_delete_adjunto);

        foreach ($delete_adjuntos as $adjunto_id) {
            // Obtener la ruta del archivo para poder eliminarlo del servidor
            $stmt_get_adjunto->bind_param('ii', $adjunto_id, $id);
            $stmt_get_adjunto->execute();
            $result = $stmt_get_adjunto->get_result();
            if ($row = $result->fetch_assoc()) {
                $ruta_archivo_fs = __DIR__ . '/../' . $row['ruta_archivo'];
                if (file_exists($ruta_archivo_fs)) {
                    unlink($ruta_archivo_fs); // Eliminar archivo del servidor
                }
            }
            $stmt_get_adjunto->free_result();

            // Eliminar el registro de la base de datos
            $stmt_delete_adjunto->bind_param('ii', $adjunto_id, $id);
            if (!$stmt_delete_adjunto->execute()) {
                throw new Exception('Error al eliminar el adjunto de la base de datos.');
            }
        }
        $stmt_get_adjunto->close();
        $stmt_delete_adjunto->close();
    }

    // 2. Actualizar la información del pedido
    $sql = "UPDATE pedidos SET nombre_cliente = ?, correo_cliente = ?, numero_cliente = ?, notas = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta de actualización de pedido: ' . $conn->error);
    }
    $stmt->bind_param('ssssi', $nombre_cliente, $email_cliente, $movil_cliente, $notas, $id);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el pedido: ' . $stmt->error);
    }
    $stmt->close();

    // 3. Procesar nuevos archivos adjuntos si se han subido
    if (isset($_FILES['adjuntos']) && !empty($_FILES['adjuntos']['name'][0])) {
        $upload_dir_fs = __DIR__ . '/../uploads/pedidos/';
        $adjuntos = $_FILES['adjuntos'];

        foreach ($adjuntos['name'] as $key => $name) {
            if ($adjuntos['error'][$key] == UPLOAD_ERR_OK) {
                $tmp_name = $adjuntos['tmp_name'][$key];
                $file_name = 'GUIA' . strtoupper(uniqid()) . '_' . basename($name);
                $ruta_final_fs = $upload_dir_fs . $file_name;

                if (move_uploaded_file($tmp_name, $ruta_final_fs)) {
                    $ruta_adjunto_db = 'uploads/pedidos/' . $file_name;
                    $sql_adjunto = "INSERT INTO pedido_adjuntos (pedido_id, ruta_archivo) VALUES (?, ?)";
                    $stmt_adjunto = $conn->prepare($sql_adjunto);
                    if ($stmt_adjunto === false) {
                        throw new Exception('Error al preparar la consulta de inserción de adjunto: ' . $conn->error);
                    }
                    $stmt_adjunto->bind_param('is', $id, $ruta_adjunto_db);
                    if (!$stmt_adjunto->execute()) {
                        throw new Exception('Error al guardar el adjunto en la base de datos: ' . $stmt_adjunto->error);
                    }
                    $stmt_adjunto->close();
                } else {
                    throw new Exception('Error al subir el archivo adjunto: ' . $name);
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>