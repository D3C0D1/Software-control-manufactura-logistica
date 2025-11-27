<?php
session_start();
require_once 'db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Verificar que se recibió el pedido_id y el archivo
if (!isset($_POST['pedido_id']) || !isset($_FILES['nuevo_archivo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$pedido_id = intval($_POST['pedido_id']);
$file = $_FILES['nuevo_archivo'];

// Verificar que no hubo errores en la subida
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    exit;
}

// Obtener información del pedido para generar el nombre del archivo
$sql = "SELECT numero_guia FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
    exit;
}

$pedido = $result->fetch_assoc();
$guia = $pedido['numero_guia'];

// Validar tipo de archivo
$allowed_extensions = ['zip', 'rar', '7z', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'ai', 'psd'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit;
}

// Crear directorio si no existe
$upload_dir_fs = __DIR__ . '/../uploads/pedidos/';
if (!is_dir($upload_dir_fs)) {
    mkdir($upload_dir_fs, 0777, true);
}

// Generar nombre único para el archivo
$timestamp = date('YmdHis');
$file_name = $guia . '_nuevo_' . $timestamp . '_' . basename($file['name']);
$ruta_final_fs = $upload_dir_fs . $file_name;

// Mover el archivo
if (move_uploaded_file($file['tmp_name'], $ruta_final_fs)) {
    // Guardar en la base de datos
    $ruta_adjunto_db = 'uploads/pedidos/' . $file_name;
    $sql_adjunto = "INSERT INTO pedido_adjuntos (pedido_id, ruta_archivo) VALUES (?, ?)";
    $stmt_adjunto = $conn->prepare($sql_adjunto);
    $stmt_adjunto->bind_param('is', $pedido_id, $ruta_adjunto_db);
    
    if ($stmt_adjunto->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Archivo subido exitosamente',
            'archivo' => [
                'nombre' => basename($file['name']),
                'ruta' => $ruta_adjunto_db,
                'tipo' => 'nuevo'
            ]
        ]);
    } else {
        // Si falla la inserción en BD, eliminar el archivo
        unlink($ruta_final_fs);
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
    }
    
    $stmt_adjunto->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al mover el archivo']);
}

$stmt->close();
$conn->close();
?>